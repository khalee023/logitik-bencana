<?php

namespace App\Services;

use App\Models\ArmadaKendaraan;
use App\Models\DemandKebutuhan;
use App\Models\Desa;
use App\Models\PusatDistribusi;
use App\Models\Rute;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OptimizationService
{
    private string $fastApiUrl;

    public function __construct()
    {
        $this->fastApiUrl = config('services.fastapi.url', 'http://localhost:8001');
    }

    /**
     * Fase 1: Agregasi data dan pengiriman payload ke FastAPI.
     * Tidak membuka transaksi DB apapun — murni query read-only (kecuali urgency update).
     *
     * @return array  Hasil simulasi dari FastAPI
     * @throws \Exception
     */
    public function triggerSimulation(): array
    {
        // Phase 1A: ML Urgency Prediction — update scores sebelum build payload
        $this->predictUrgency();

        // 1. Ambil semua pusat distribusi aktif
        $depots = PusatDistribusi::aktif()->get();

        if ($depots->isEmpty()) {
            throw new \Exception('Tidak ada pusat distribusi aktif.');
        }

        // 2. Ambil semua demand berstatus Queued
        $demands = DemandKebutuhan::with(['desa', 'barang'])
            ->byStatus('Queued')
            ->get();

        if ($demands->isEmpty()) {
            throw new \Exception('Tidak ada permintaan bantuan yang mengantre (status: Queued).');
        }

        // 3. Ambil armada tersedia
        $fleet = ArmadaKendaraan::available()->get();

        if ($fleet->isEmpty()) {
            throw new \Exception('Tidak ada armada kendaraan yang tersedia.');
        }

        // 4. Ambil graf rute
        $rutes = Rute::all();

        // 5. Bangun struktur node
        $depotNodes = $depots->map(fn($d) => [
            'id_pusat' => $d->id,
            'lat' => (float) $d->lat,
            'lng' => (float) $d->long_decimal,
        ])->values()->toArray();

        // Grup demand per desa
        $desaDemands = $demands->groupBy('id_desa');
        $demandNodes = [];

        foreach ($desaDemands as $idDesa => $desaDemandGroup) {
            $desa = $desaDemandGroup->first()->desa;
            $totalBerat = 0;
            $totalVol = 0;
            $maxUrgency = 0;
            $demandIds = [];

            foreach ($desaDemandGroup as $demand) {
                $totalBerat += $demand->kuantitas * (float) $demand->barang->berat_kg;
                $totalVol += $demand->kuantitas * (float) $demand->barang->volume_m3;
                $maxUrgency = max($maxUrgency, (float) $demand->urgency_score);
                $demandIds[] = $demand->id;
            }

            $demandNodes[] = [
                'id_desa' => $desa->id,
                'lat' => (float) $desa->lat,
                'lng' => (float) $desa->long_decimal,
                // Cap demand in solver payload to prevent NO_SOLUTION when single node exceeds vehicle max capacity
                'berat_demand' => min(round($totalBerat, 2), 2400.0),
                'vol_demand' => min(round($totalVol, 4), 9.0),
                'urgency_score' => round($maxUrgency, 2),
                'window_start' => 0,
                'window_end' => 1440, // 24 jam
                'demand_ids' => $demandIds,
            ];
        }

        // 6. Bangun matriks jarak dengan Floyd-Warshall pada FULL GRAPH
        // Ambil SEMUA node untuk graph traversal
        $allDesaIds = Desa::pluck('id')->toArray();
        $allDepotIds = $depots->pluck('id')->toArray();
        $fullGraphIds = array_merge($allDepotIds, $allDesaIds);
        $fullN = count($fullGraphIds);
        $fullIdToIdx = array_flip($fullGraphIds);

        $fullDist = array_fill(0, $fullN, array_fill(0, $fullN, 999999.0));
        $fullTime = array_fill(0, $fullN, array_fill(0, $fullN, 9999));
        $speed = 40.0;

        for ($i = 0; $i < $fullN; $i++) {
            $fullDist[$i][$i] = 0.0;
            $fullTime[$i][$i] = 0;
        }

        // Isi edge aktual
        foreach ($rutes as $rute) {
            $srcIdx = $fullIdToIdx[$rute->id_titik_asal] ?? null;
            $dstIdx = $fullIdToIdx[$rute->id_titik_tujuan] ?? null;

            if ($srcIdx !== null && $dstIdx !== null) {
                $distance = $rute->status_akses_terbuka ? $rute->jarak_km : 999999.0;
                $time = $rute->status_akses_terbuka ? (int) ceil(($rute->jarak_km / $speed) * 60) : 9999;

                $fullDist[$srcIdx][$dstIdx] = $distance;
                $fullDist[$dstIdx][$srcIdx] = $distance;
                $fullTime[$srcIdx][$dstIdx] = $time;
                $fullTime[$dstIdx][$srcIdx] = $time;
            }
        }

        // Floyd-Warshall
        for ($k = 0; $k < $fullN; $k++) {
            for ($i = 0; $i < $fullN; $i++) {
                for ($j = 0; $j < $fullN; $j++) {
                    if ($fullDist[$i][$k] + $fullDist[$k][$j] < $fullDist[$i][$j]) {
                        $fullDist[$i][$j] = $fullDist[$i][$k] + $fullDist[$k][$j];
                        $fullTime[$i][$j] = $fullTime[$i][$k] + $fullTime[$k][$j];
                    }
                }
            }
        }

        // 7. Ekstrak submatrix hanya untuk node yang dikirim ke OR-Tools (Depots + Active Demands)
        $allNodeIds = array_merge(
            $depots->pluck('id')->toArray(),
            array_column($demandNodes, 'id_desa')
        );

        $n = count($allNodeIds);
        $distanceMatrix = array_fill(0, $n, array_fill(0, $n, 0.0));
        $timeMatrix = array_fill(0, $n, array_fill(0, $n, 0));

        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                $fullIdxI = $fullIdToIdx[$allNodeIds[$i]];
                $fullIdxJ = $fullIdToIdx[$allNodeIds[$j]];
                $distanceMatrix[$i][$j] = $fullDist[$fullIdxI][$fullIdxJ];
                $timeMatrix[$i][$j] = $fullTime[$fullIdxI][$fullIdxJ];
            }
        }

        // 7. Bangun fleet nodes
        $fleetNodes = $fleet->map(fn($v) => [
            'id_armada' => $v->id,
            'id_pusat' => $v->id_pusat,
            'max_berat' => (float) $v->max_berat_kg,
            'max_vol' => (float) $v->max_vol_m3,
        ])->values()->toArray();

        // 8. Kirim payload ke FastAPI
        $payload = [
            'depots' => $depotNodes,
            'nodes' => $demandNodes,
            'fleet' => $fleetNodes,
            'distance_matrix' => $distanceMatrix,
            'time_matrix' => $timeMatrix,
            'speed_kmh' => $speed,
            'alpha' => 1.0,
            'beta' => 0.01,
        ];

        Log::info('Optimization payload dispatched to FastAPI', [
            'num_depots' => count($depotNodes),
            'num_demand_nodes' => count($demandNodes),
            'num_vehicles' => count($fleetNodes),
            'distance_matrix' => $distanceMatrix,
            'demand_weights' => array_column($demandNodes, 'berat_demand')
        ]);

        try {
            $response = Http::timeout(35)
                ->post("{$this->fastApiUrl}/api/optimize", $payload);

            if ($response->failed()) {
                throw new \Exception("FastAPI returned HTTP {$response->status()}: {$response->body()}");
            }

            return $response->json();
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new \Exception("Gagal terhubung ke microservice optimasi. Pastikan FastAPI berjalan di {$this->fastApiUrl}. Error: {$e->getMessage()}");
        }
    }

    /**
     * Fase 2: State-Commit transaksional dengan pessimistic locking.
     *
     * @param  array  $simulationResult
     * @return array  Daftar manifest ID yang dibuat
     * @throws \Exception
     */
    public function approveSimulation(array $simulationResult): array
    {
        $manifestIds = [];

        DB::beginTransaction();

        try {
            foreach ($simulationResult['routes'] as $route) {
                $idArmada = $route['id_armada'];

                // Lock armada
                $armada = DB::table('armada_kendaraan')
                    ->where('id', $idArmada)
                    ->where('status', 'Available')
                    ->lockForUpdate()
                    ->first();

                if (!$armada) {
                    throw new \Exception("Armada ID {$idArmada} tidak tersedia.");
                }

                // Dapatkan pusat distribusi armada ini
                $idPusat = $armada->id_pusat;

                // Kumpulkan demand IDs dari semua stop dalam rute
                $routeStops = $route['stops'];
                foreach ($routeStops as &$stop) {
                    if ($stop['id_desa'] !== null) {
                        $stop['delivered_items'] = [];
                        // Cari demand IDs terkait desa ini
                        $desaDemands = DemandKebutuhan::with('barang')->where('id_desa', $stop['id_desa'])
                            ->byStatus('Queued')
                            ->get();
                        
                        foreach ($desaDemands as $demand) {
                            $stok = DB::table('stok_pusat')
                                ->where('id_pusat', $idPusat)
                                ->where('id_barang', $demand->id_barang)
                                ->lockForUpdate()
                                ->first();

                            if (!$stok || $stok->total_kuantitas < $demand->kuantitas) {
                                // Skip demand ini jika stok tidak mencukupi, jangan batalkan seluruh manifest
                                continue;
                            }

                            // Deduksi stok
                            DB::table('stok_pusat')
                                ->where('id_pusat', $idPusat)
                                ->where('id_barang', $demand->id_barang)
                                ->update([
                                    'total_kuantitas' => $stok->total_kuantitas - $demand->kuantitas,
                                    'updated_at' => now(),
                                ]);

                            // Tandai Manifested
                            DB::table('demand_kebutuhan')
                                ->where('id', $demand->id)
                                ->update([
                                    'status' => 'Manifested',
                                    'updated_at' => now(),
                                ]);

                            // Simpan log pengiriman ke dalam route stop
                            $stop['delivered_items'][] = [
                                'nama_barang' => $demand->barang->nama,
                                'kuantitas' => $demand->kuantitas
                            ];
                        }
                    }
                }

                // Buat manifest
                $manifestId = DB::table('manifest_pengiriman')->insertGetId([
                    'kode_manifest' => \Ramsey\Uuid\Uuid::uuid4()->getBytes(),
                    'id_pusat' => $idPusat,
                    'id_armada' => $idArmada,
                    'status' => 'In-Transit',
                    'route_json' => json_encode($routeStops),
                    'waktu_berangkat' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Update status armada
                DB::table('armada_kendaraan')
                    ->where('id', $idArmada)
                    ->update(['status' => 'In-Transit']);

                $manifestIds[] = $manifestId;
            }

            DB::commit();

            Log::info('Two-Phase Commit completed successfully', [
                'manifest_count' => count($manifestIds),
                'manifest_ids' => $manifestIds,
            ]);

            return $manifestIds;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Two-Phase Commit ROLLBACK: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Phase 1A: ML Urgency Prediction.
     * Collects desa feature vectors, calls FastAPI /api/predict-urgency,
     * and updates urgency_score + target_deadline_jam on Queued demands.
     *
     * @throws \Exception
     */
    public function predictUrgency(): void
    {
        // Get all desa IDs that have Queued demands
        $desaIds = DemandKebutuhan::byStatus('Queued')
            ->distinct()
            ->pluck('id_desa');

        if ($desaIds->isEmpty()) {
            return; // No queued demands, nothing to predict
        }

        // Collect feature vectors for each desa
        $villages = Desa::whereIn('id', $desaIds)->get()->map(fn($d) => [
            'id' => $d->id,
            'populasi' => $d->populasi,
            'korban_selamat' => $d->korban_selamat,
            'jumlah_orang_sakit' => $d->jumlah_orang_sakit ?? 0,
            'persentase_infrastruktur_rusak' => (float) ($d->persentase_infrastruktur_rusak ?? 0),
            'status_isolasi' => (bool) ($d->status_isolasi ?? false),
        ])->values()->toArray();

        try {
            $response = Http::timeout(30)
                ->post("{$this->fastApiUrl}/api/predict-urgency", [
                    'villages' => $villages,
                ]);

            if ($response->failed()) {
                Log::warning('ML predict-urgency failed, using existing scores.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return;
            }

            $urgencyScores = $response->json('urgency_scores', []);

            // Update urgency_score and target_deadline_jam on Queued demands
            foreach ($urgencyScores as $desaId => $score) {
                $deadline = round(72 / ($score + 1), 2);

                DemandKebutuhan::where('id_desa', $desaId)
                    ->byStatus('Queued')
                    ->update([
                        'urgency_score' => $score,
                        'target_deadline_jam' => $deadline,
                    ]);
            }

            Log::info('ML urgency prediction completed', [
                'villages_predicted' => count($urgencyScores),
            ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('ML predict-urgency connection failed, using existing scores.', [
                'error' => $e->getMessage(),
            ]);
            // Non-fatal: proceed with existing urgency scores
        }
    }
}
