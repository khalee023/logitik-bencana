<?php

namespace App\Http\Controllers;

use App\Models\DemandKebutuhan;
use App\Models\ManifestPengiriman;
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

        return view('koordinator.dashboard', compact('stats'));
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
                ->with('success', 'Simulasi optimasi berhasil. Silakan review hasil rute.');
        } catch (\Exception $e) {
            Log::error('Optimization trigger failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Gagal menjalankan optimasi: ' . $e->getMessage());
        }
    }

    /**
     * Halaman review simulasi — Human-in-the-Loop.
     */
    public function reviewSimulation(Request $request)
    {
        $result = $request->session()->get('simulation_result');

        if (!$result) {
            return redirect()->route('koordinator.dashboard')
                ->with('error', 'Tidak ada hasil simulasi yang tersedia untuk direview.');
        }

        return view('koordinator.review', compact('result'));
    }

    /**
     * Fase 2: Menyetujui simulasi — State-Commit transaksional.
     */
    public function approveSimulation(Request $request)
    {
        $result = $request->session()->get('simulation_result');

        if (!$result) {
            return redirect()->route('koordinator.dashboard')
                ->with('error', 'Tidak ada hasil simulasi yang tersedia.');
        }

        try {
            $service = new OptimizationService();
            $manifestIds = $service->approveSimulation($result);

            // Hapus hasil simulasi dari session
            $request->session()->forget('simulation_result');

            return redirect()->route('koordinator.dashboard')
                ->with('success', 'State-Commit berhasil! ' . count($manifestIds) . ' manifest pengiriman diterbitkan.');
        } catch (\Exception $e) {
            Log::error('Approval failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Gagal menyetujui simulasi: ' . $e->getMessage());
        }
    }

    /**
     * Menolak hasil simulasi.
     */
    public function rejectSimulation(Request $request)
    {
        $request->session()->forget('simulation_result');
        return redirect()->route('koordinator.dashboard')
            ->with('success', 'Hasil simulasi ditolak. Anda dapat menjalankan optimasi ulang.');
    }

    /**
     * Menyelesaikan manifest — menandai pengiriman selesai.
     * Transisi: In-Transit → Delivered, demands → Fulfilled, armada → Available
     */
    public function completeManifest(Request $request, ManifestPengiriman $manifest)
    {
        if ($manifest->status !== 'In-Transit') {
            return redirect()->back()
                ->with('error', 'Hanya manifest berstatus In-Transit yang dapat diselesaikan.');
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
                            ->update(['status' => 'Fulfilled']);
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
                ->with('success', "Manifest #{$manifest->id} berhasil diselesaikan. Armada dikembalikan ke pool.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Complete manifest failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Gagal menyelesaikan manifest: ' . $e->getMessage());
        }
    }

    /**
     * Riwayat manifest pengiriman.
     */
    public function manifestHistory()
    {
        $manifests = ManifestPengiriman::with(['pusatDistribusi', 'armada'])
            ->latest()
            ->get();

        return view('koordinator.manifest', compact('manifests'));
    }
}
