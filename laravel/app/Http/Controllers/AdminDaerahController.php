<?php

namespace App\Http\Controllers;

use App\Models\DemandKebutuhan;
use App\Models\Desa;
use App\Models\MasterBantuan;
use App\Services\BurnRateService;
use Illuminate\Http\Request;

class AdminDaerahController extends Controller
{
    /**
     * Dashboard Admin Daerah — input kebutuhan desa sesuai yurisdiksi kabupaten.
     */
    public function dashboard()
    {
        $idKab = auth()->user()->id_kabupaten;

        $desaQuery = Desa::withCount('demands');
        if ($idKab) {
            $desaQuery->where('id_kabupaten', $idKab);
        }
        $desa = $desaQuery->get();

        $desaIds = $desa->pluck('id');
        $totalDemand = DemandKebutuhan::whereIn('id_desa', $desaIds)->count();
        $draftCount = DemandKebutuhan::whereIn('id_desa', $desaIds)->byStatus('Draft')->count();
        $queuedCount = DemandKebutuhan::whereIn('id_desa', $desaIds)->byStatus('Queued')->count();

        return view('admin-daerah.dashboard', compact('desa', 'totalDemand', 'draftCount', 'queuedCount'));
    }

    /**
     * Daftar demand kebutuhan — scoped ke kabupaten user.
     */
    public function demandIndex()
    {
        $idKab = auth()->user()->id_kabupaten;

        $desaIds = $idKab
            ? Desa::where('id_kabupaten', $idKab)->pluck('id')
            : Desa::pluck('id');

        $demands = DemandKebutuhan::with(['desa', 'barang'])
            ->whereIn('id_desa', $desaIds)
            ->latest()
            ->get();

        $desaList = $idKab
            ? Desa::where('id_kabupaten', $idKab)->get()
            : Desa::all();
        $barangList = MasterBantuan::all();

        return view('admin-daerah.demand', compact('demands', 'desaList', 'barangList'));
    }

    /**
     * Menyimpan demand kebutuhan baru (status: Draft).
     */
    public function demandStore(Request $request)
    {
        $request->validate([
            'id_desa' => 'required|exists:desa,id',
            'id_barang' => 'required|array|min:1',
            'id_barang.*' => 'exists:master_bantuan,id',
            'kuantitas' => 'required|array|min:1',
            'kuantitas.*' => 'integer|min:1',
        ]);

        $batchId = \Ramsey\Uuid\Uuid::uuid4()->getBytes();

        for ($i = 0; $i < count($request->id_barang); $i++) {
            DemandKebutuhan::create([
                'kode_batch' => $batchId,
                'id_desa' => $request->id_desa,
                'id_barang' => $request->id_barang[$i],
                'kuantitas' => $request->kuantitas[$i],
                'status' => 'Draft',
            ]);
            // Generate a slightly different batch ID for DB uniqueness if it's required to be unique per row
            // Oh wait, the migration has `$table->binary('kode_batch')->unique();` !
            // It MUST be unique PER ROW!
            $batchId = \Ramsey\Uuid\Uuid::uuid4()->getBytes();
        }

        // Hitung urgency score menggunakan Burn-Rate formula
        $burnRate = new BurnRateService();
        $score = $burnRate->updateDesaUrgencyScores($request->id_desa);

        return redirect()->back()->with('success', "Kebutuhan berhasil dicatat. Urgency Score: {$score}");
    }

    /**
     * Mengubah status demand dari Draft → Queued.
     */
    public function demandQueue(Request $request, DemandKebutuhan $demand)
    {
        if ($demand->status !== 'Draft') {
            return redirect()->back()->with('error', 'Hanya demand berstatus Draft yang dapat diantrikan.');
        }

        $demand->update(['status' => 'Queued']);
        return redirect()->back()->with('success', 'Demand berhasil dipindahkan ke antrean optimasi.');
    }

    /**
     * Update data demand.
     */
    public function demandUpdate(Request $request, DemandKebutuhan $demand)
    {
        $request->validate([
            'kuantitas' => 'required|integer|min:1',
        ]);

        $demand->update(['kuantitas' => $request->kuantitas]);

        // Recalculate urgency
        $burnRate = new BurnRateService();
        $burnRate->updateDesaUrgencyScores($demand->id_desa);

        return redirect()->back()->with('success', 'Data kebutuhan berhasil diperbarui.');
    }
}
