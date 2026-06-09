<?php

namespace App\Http\Controllers;

use App\Models\ArmadaKendaraan;
use App\Models\Kabupaten;
use App\Models\MasterBantuan;
use App\Models\PusatDistribusi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminPusatController extends Controller
{
    /**
     * Dashboard Admin Pusat — overview rantai pasok global.
     */
    public function dashboard()
    {
        $stats = [
            'total_pusat' => PusatDistribusi::aktif()->count(),
            'total_armada' => ArmadaKendaraan::count(),
            'armada_available' => ArmadaKendaraan::available()->count(),
            'total_barang' => MasterBantuan::count(),
            'total_stok' => DB::table('stok_pusat')->sum('total_kuantitas'),
        ];

        $pusatDistribusi = PusatDistribusi::with('kabupaten')->get();
        $armada = ArmadaKendaraan::with('pusatDistribusi')->get();

        return view('admin-pusat.dashboard', compact('stats', 'pusatDistribusi', 'armada'));
    }

    // === PUSAT DISTRIBUSI CRUD ===

    public function pusatDistribusiIndex()
    {
        $data = PusatDistribusi::with('kabupaten')->get();
        $kabupaten = Kabupaten::all();
        return view('admin-pusat.pusat-distribusi', compact('data', 'kabupaten'));
    }

    public function pusatDistribusiStore(Request $request)
    {
        $request->validate([
            'id_kabupaten' => 'required|exists:kabupaten,id',
            'nama' => 'required|string|max:150',
            'lat' => 'required|numeric|between:-90,90',
            'long_decimal' => 'required|numeric|between:-180,180',
        ]);

        PusatDistribusi::create($request->only(['id_kabupaten', 'nama', 'lat', 'long_decimal']));
        return redirect()->back()->with('success', 'Pusat distribusi berhasil ditambahkan.');
    }

    public function pusatDistribusiUpdate(Request $request, PusatDistribusi $pusat)
    {
        $request->validate([
            'nama' => 'required|string|max:150',
            'lat' => 'required|numeric|between:-90,90',
            'long_decimal' => 'required|numeric|between:-180,180',
            'status_aktif' => 'required|boolean',
        ]);

        $pusat->update($request->only(['nama', 'lat', 'long_decimal', 'status_aktif']));
        return redirect()->back()->with('success', 'Pusat distribusi berhasil diperbarui.');
    }

    // === MASTER BANTUAN CRUD ===

    public function masterBantuanIndex()
    {
        $data = MasterBantuan::all();
        return view('admin-pusat.master-bantuan', compact('data'));
    }

    public function masterBantuanStore(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:150',
            'kategori' => 'required|in:Medis,Air,Ransum,Tenda',
            'berat_kg' => 'required|numeric|min:0.01',
            'volume_m3' => 'required|numeric|min:0.0001',
        ]);

        MasterBantuan::create($request->only(['nama', 'kategori', 'berat_kg', 'volume_m3']));
        return redirect()->back()->with('success', 'Item bantuan berhasil ditambahkan.');
    }

    // === STOK PUSAT ===

    public function stokIndex()
    {
        $stok = DB::table('stok_pusat')
            ->join('pusat_distribusi', 'stok_pusat.id_pusat', '=', 'pusat_distribusi.id')
            ->join('master_bantuan', 'stok_pusat.id_barang', '=', 'master_bantuan.id')
            ->select('stok_pusat.*', 'pusat_distribusi.nama as nama_pusat', 'master_bantuan.nama as nama_barang', 'master_bantuan.kategori')
            ->get();

        $pusatList = PusatDistribusi::aktif()->get();
        $barangList = MasterBantuan::all();

        return view('admin-pusat.stok', compact('stok', 'pusatList', 'barangList'));
    }

    public function stokUpdate(Request $request)
    {
        $request->validate([
            'id_pusat' => 'required|exists:pusat_distribusi,id',
            'id_barang' => 'required|exists:master_bantuan,id',
            'total_kuantitas' => 'required|integer|min:0',
        ]);

        DB::table('stok_pusat')->updateOrInsert(
            ['id_pusat' => $request->id_pusat, 'id_barang' => $request->id_barang],
            ['total_kuantitas' => $request->total_kuantitas, 'updated_at' => now()]
        );

        return redirect()->back()->with('success', 'Stok berhasil diperbarui.');
    }

    // === ARMADA ===

    public function armadaIndex()
    {
        $data = ArmadaKendaraan::with('pusatDistribusi')->get();
        $pusatList = PusatDistribusi::aktif()->get();
        return view('admin-pusat.armada', compact('data', 'pusatList'));
    }

    public function armadaStore(Request $request)
    {
        $request->validate([
            'id_pusat' => 'required|exists:pusat_distribusi,id',
            'plat_nomor' => 'required|string|max:20|unique:armada_kendaraan,plat_nomor',
            'max_berat_kg' => 'required|numeric|min:100',
            'max_vol_m3' => 'required|numeric|min:0.1',
        ]);

        ArmadaKendaraan::create($request->only(['id_pusat', 'plat_nomor', 'max_berat_kg', 'max_vol_m3']));
        return redirect()->back()->with('success', 'Kendaraan armada berhasil ditambahkan.');
    }
}
