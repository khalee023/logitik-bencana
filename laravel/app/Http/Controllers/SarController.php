<?php

namespace App\Http\Controllers;

use App\Models\Desa;
use App\Models\Rute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SarController extends Controller
{
    /**
     * Dashboard Tim SAR — jaringan rute dan pengelolaan akses jalan.
     */
    public function dashboard()
    {
        $rutes = Rute::all();
        $totalRute = $rutes->count();
        $ruteTerbuka = $rutes->where('status_akses_terbuka', true)->count();
        $ruteTerblokir = $rutes->where('status_akses_terbuka', false)->count();

        $desaList = Desa::all();

        return view('sar.dashboard', compact('rutes', 'totalRute', 'ruteTerbuka', 'ruteTerblokir', 'desaList'));
    }

    /**
     * Toggle status akses rute — SAR-controlled.
     * Endpoint: POST /sar/rute/toggle-access
     */
    public function toggleRouteAccess(Request $request): JsonResponse
    {
        $request->validate([
            'id_rute' => 'required|exists:rute,id',
        ]);

        $rute = Rute::findOrFail($request->id_rute);
        $rute->status_akses_terbuka = !$rute->status_akses_terbuka;
        $rute->save();

        $statusText = $rute->status_akses_terbuka ? 'OPEN' : 'BLOCKED';

        return response()->json([
            'status' => 'SUCCESS',
            'message' => "Route ID {$rute->id} access changed to {$statusText}.",
            'rute' => [
                'id' => $rute->id,
                'id_titik_asal' => $rute->id_titik_asal,
                'id_titik_tujuan' => $rute->id_titik_tujuan,
                'status_akses_terbuka' => $rute->status_akses_terbuka,
            ],
        ]);
    }

    /**
     * Update status desa — SAR field operations.
     * Endpoint: POST /sar/desa/update-status
     */
    public function updateDesaStatus(Request $request): JsonResponse
    {
        $request->validate([
            'id_desa' => 'required|exists:desa,id',
            'status_aman' => 'sometimes|boolean',
            'status_isolasi' => 'sometimes|boolean',
        ]);

        $desa = Desa::findOrFail($request->id_desa);

        $updated = [];
        if ($request->has('status_aman')) {
            $desa->status_aman = $request->status_aman;
            $updated['status_aman'] = $desa->status_aman;
        }
        if ($request->has('status_isolasi')) {
            $desa->status_isolasi = $request->status_isolasi;
            $updated['status_isolasi'] = $desa->status_isolasi;
        }

        $desa->save();

        // Recalculate urgency score via ML predictor
        try {
            $optService = new \App\Services\OptimizationService();
            $optService->predictUrgency();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('ML Urgency update failed on status update: ' . $e->getMessage());
        }

        return response()->json([
            'status' => 'SUCCESS',
            'message' => "Village '{$desa->nama}' status updated successfully.",
            'desa' => [
                'id' => $desa->id,
                'nama' => $desa->nama,
                'status_aman' => $desa->status_aman,
                'status_isolasi' => $desa->status_isolasi,
            ],
        ]);
    }

    /**
     * Update metrik desa — SAR field operations.
     * Endpoint: POST /sar/desa/update-metrics
     */
    public function updateDesaMetrics(Request $request): JsonResponse
    {
        $request->validate([
            'id_desa' => 'required|exists:desa,id',
            'populasi' => 'required|integer|min:0',
            'korban_selamat' => 'required|integer|min:0',
            'jumlah_orang_sakit' => 'required|integer|min:0',
            'persentase_infrastruktur_rusak' => 'required|numeric|between:0,100',
        ]);

        $desa = Desa::findOrFail($request->id_desa);

        $desa->populasi = $request->populasi;
        $desa->korban_selamat = $request->korban_selamat;
        $desa->jumlah_orang_sakit = $request->jumlah_orang_sakit;
        $desa->persentase_infrastruktur_rusak = $request->persentase_infrastruktur_rusak;
        $desa->save();

        // Recalculate urgency score via ML predictor
        try {
            $optService = new \App\Services\OptimizationService();
            $optService->predictUrgency();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('ML Urgency update failed on metrics update: ' . $e->getMessage());
        }

        return response()->json([
            'status' => 'SUCCESS',
            'message' => "Village '{$desa->nama}' metrics updated successfully.",
            'desa' => [
                'id' => $desa->id,
                'populasi' => $desa->populasi,
                'korban_selamat' => $desa->korban_selamat,
                'jumlah_orang_sakit' => $desa->jumlah_orang_sakit,
                'persentase_infrastruktur_rusak' => $desa->persentase_infrastruktur_rusak,
            ],
        ]);
    }
}

