<?php

namespace App\Http\Controllers;

use App\Models\ArmadaKendaraan;
use App\Models\DemandKebutuhan;
use App\Models\Desa;
use App\Models\ManifestPengiriman;
use App\Models\PusatDistribusi;
use App\Services\OptimizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KoordinatorController extends Controller
{
    /**
     * Dashboard Koordinator — Command Center dengan tombol optimasi global.
     */
    public function dashboard()
    {
        $stats = [
            'demand_queued' => DemandKebutuhan::byStatus('Queued')->count(),
            'demand_draft' => DemandKebutuhan::byStatus('Draft')->count(),
            'demand_manifested' => DemandKebutuhan::byStatus('Manifested')->count(),
            'demand_fulfilled' => DemandKebutuhan::byStatus('Fulfilled')->count(),
            'total_manifest' => ManifestPengiriman::count(),
            'manifest_transit' => ManifestPengiriman::where('status', 'In-Transit')->count(),
        ];

        // Queued demands preview (top urgency, limit 10)
        $queuedDemands = DemandKebutuhan::with(['desa', 'barang'])
            ->byStatus('Queued')
            ->orderByDesc('urgency_score')
            ->limit(10)
            ->get();

        return view('koordinator.dashboard', compact('stats', 'queuedDemands'));
    }

    /**
     * Update skor urgensi secara manual melalui ML model
     */
    public function updateUrgency()
    {
        try {
            $optService = new \App\Services\OptimizationService();
            $optService->predictUrgency();
            
            return redirect()->back()->with('success', 'Urgency scores updated successfully via AI model.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Manual Urgency update failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update urgency scores: ' . $e->getMessage());
        }
    }

    /**
     * Fase 1: Memicu kalkulasi optimasi global.
     * Mengirim HTTP POST ke FastAPI — tidak ada perubahan DB.
     */
    public function triggerOptimization(Request $request)
    {
        try {
            $service = new OptimizationService();
            $result = $service->triggerSimulation();

            // Simpan hasil simulasi di session untuk review
            $request->session()->put('simulation_result', $result);

            return redirect()->route('koordinator.review')
                ->with('success', 'Optimization simulation completed successfully. Please review the proposed routes.');
        } catch (\Exception $e) {
            Log::error('Optimization trigger failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to run optimization: ' . $e->getMessage());
        }
    }

    /**
     * Halaman review simulasi — Human-in-the-Loop.
     * Enriched with map data for Leaflet rendering.
     */
    public function reviewSimulation(Request $request)
    {
        $result = $request->session()->get('simulation_result');

        if (!$result) {
            return redirect()->route('koordinator.dashboard')
                ->with('error', 'No simulation results available for review.');
        }

        // Build desa name lookup for resolved village names
        $desaIds = collect();
        foreach ($result['routes'] as $route) {
            foreach ($route['stops'] as $stop) {
                if (!is_null($stop['id_desa'])) {
                    $desaIds->push($stop['id_desa']);
                }
            }
        }
        $desaNames = Desa::whereIn('id', $desaIds->unique())
            ->pluck('nama', 'id')
            ->toArray();

        // Build map data for Leaflet
        $mapData = ['depots' => [], 'desa' => [], 'armadas' => []];

        // Get all active depots for map markers
        $depots = PusatDistribusi::aktif()->get();
        foreach ($depots as $depot) {
            $mapData['depots'][$depot->id] = [
                'id' => $depot->id,
                'lat' => (float) $depot->lat,
                'lng' => (float) $depot->long_decimal,
                'nama' => $depot->nama,
            ];
        }

        // Map fleet ID to their depot ID
        $routeArmadaIds = collect($result['routes'])->pluck('id_armada')->unique();
        $armadas = ArmadaKendaraan::whereIn('id', $routeArmadaIds)->get();
        foreach ($armadas as $armada) {
            $mapData['armadas'][$armada->id] = $armada->id_pusat;
        }

        // Get all desa coordinates for route plotting
        $desaList = Desa::whereIn('id', $desaIds->unique())->get();
        foreach ($desaList as $d) {
            $mapData['desa'][] = [
                'id' => $d->id,
                'lat' => (float) $d->lat,
                'lng' => (float) $d->long_decimal,
                'nama' => $d->nama,
            ];
        }

        return view('koordinator.review', compact('result', 'desaNames', 'mapData'));
    }

    /**
     * Fase 2: Menyetujui simulasi — State-Commit transaksional.
     */
    public function approveSimulation(Request $request)
    {
        $result = $request->session()->get('simulation_result');

        if (!$result) {
            return redirect()->route('koordinator.dashboard')
                ->with('error', 'No simulation results available.');
        }

        try {
            $service = new OptimizationService();
            $manifestIds = $service->approveSimulation($result);

            // Hapus hasil simulasi dari session
            $request->session()->forget('simulation_result');

            return redirect()->route('koordinator.dashboard')
                ->with('success', 'State-Commit successful! ' . count($manifestIds) . ' shipping manifest(s) published.');
        } catch (\Exception $e) {
            Log::error('Approval failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to approve simulation: ' . $e->getMessage());
        }
    }

    /**
     * Menolak hasil simulasi.
     */
    public function rejectSimulation(Request $request)
    {
        $request->session()->forget('simulation_result');
        return redirect()->route('koordinator.dashboard')
            ->with('success', 'Simulation results rejected. You may re-run optimization.');
    }

    /**
     * Menyelesaikan manifest — menandai pengiriman selesai.
     * Transisi: In-Transit → Delivered, demands → Fulfilled, armada → Available
     */
    public function completeManifest(Request $request, ManifestPengiriman $manifest)
    {
        if ($manifest->status !== 'In-Transit') {
            return redirect()->back()
                ->with('error', 'Only manifests with In-Transit status can be completed.');
        }

        DB::beginTransaction();
        try {
            // 1. Update manifest status
            $manifest->update([
                'status' => 'Delivered',
                'waktu_tiba' => now(),
            ]);

            // 2. Transition all Manifested demands for villages in this route to Fulfilled
            $routeData = is_string($manifest->route_json)
                ? json_decode($manifest->route_json, true)
                : $manifest->route_json;

            if (is_array($routeData)) {
                foreach ($routeData as $stop) {
                    if (isset($stop['id_desa']) && $stop['id_desa'] !== null) {
                        DemandKebutuhan::where('id_desa', $stop['id_desa'])
                            ->byStatus('Manifested')
                            ->update(['status' => 'Delivered']);
                    }
                }
            }

            // 3. Return armada to Available
            if ($manifest->id_armada) {
                DB::table('armada_kendaraan')
                    ->where('id', $manifest->id_armada)
                    ->update(['status' => 'Available']);
            }

            DB::commit();

            Log::info('Manifest completed', [
                'manifest_id' => $manifest->id,
                'armada_id' => $manifest->id_armada,
            ]);

            return redirect()->back()
                ->with('success', "Manifest #{$manifest->id} completed successfully. Vehicle returned to pool.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Complete manifest failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to complete manifest: ' . $e->getMessage());
        }
    }

    /**
     * Riwayat manifest pengiriman — with pagination.
     */
    public function manifestHistory()
    {
        $manifests = ManifestPengiriman::with(['pusatDistribusi', 'armada'])
            ->latest()
            ->paginate(15);

        return view('koordinator.manifest', compact('manifests'));
    }
}
