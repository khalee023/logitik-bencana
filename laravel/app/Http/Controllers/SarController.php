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

        $statusText = $rute->status_akses_terbuka ? 'TERBUKA' : 'TERBLOKIR';

        return response()->json([
            'status' => 'SUCCESS',
            'message' => "Rute ID {$rute->id} berhasil diubah menjadi {$statusText}.",
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

        return response()->json([
            'status' => 'SUCCESS',
            'message' => "Status Desa '{$desa->nama}' berhasil diperbarui.",
            'desa' => [
                'id' => $desa->id,
                'nama' => $desa->nama,
                'status_aman' => $desa->status_aman,
                'status_isolasi' => $desa->status_isolasi,
            ],
        ]);
    }
}

