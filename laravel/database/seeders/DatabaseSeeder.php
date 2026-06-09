<?php

namespace Database\Seeders;

use App\Models\AkunPemerintah;
use App\Models\ArmadaKendaraan;
use App\Models\DemandKebutuhan;
use App\Models\Desa;
use App\Models\Kabupaten;
use App\Models\MasterBantuan;
use App\Models\PusatDistribusi;
use App\Models\Rute;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Akun Pemerintah (Role-Based)
        $password = Hash::make('password');
        // Note: id_kabupaten will be set after Kabupaten is created
        AkunPemerintah::insert([
            ['nama' => 'BNPB Pusat', 'email' => 'pusat@bnpb.go.id', 'password' => $password, 'role' => 'Pusat', 'id_kabupaten' => null],
            ['nama' => 'BPBD Cianjur', 'email' => 'daerah@cianjur.go.id', 'password' => $password, 'role' => 'Daerah', 'id_kabupaten' => null],
            ['nama' => 'Tim SAR Gabungan', 'email' => 'sar@basarnas.go.id', 'password' => $password, 'role' => 'SAR', 'id_kabupaten' => null],
            ['nama' => 'Koordinator Operasi', 'email' => 'koor@dlcc.go.id', 'password' => $password, 'role' => 'Koor', 'id_kabupaten' => null],
        ]);

        // 2. Kabupaten (Zona Terdampak)
        $kab = Kabupaten::create(['nama' => 'Kabupaten Cianjur']);

        // Assign kabupaten ke akun Daerah
        AkunPemerintah::where('email', 'daerah@cianjur.go.id')->update(['id_kabupaten' => $kab->id]);

        // 3. PusatDistribusi (Depot Nodes)
        $depot1 = PusatDistribusi::create([
            'id' => 1,
            'id_kabupaten' => $kab->id,
            'nama' => 'Gudang Utama Pendopo Cianjur',
            'lat' => -6.8168,
            'long_decimal' => 107.1425,
            'status_aktif' => true,
        ]);
        $depot2 = PusatDistribusi::create([
            'id' => 2,
            'id_kabupaten' => $kab->id,
            'nama' => 'Posko Aju Cipanas',
            'lat' => -6.7414,
            'long_decimal' => 107.0396,
            'status_aktif' => true,
        ]);

        // 4. Desa (Demand Nodes) — with ML feature columns
        $desaCugenang = Desa::create([
            'id' => 101,
            'id_kabupaten' => $kab->id, 'nama' => 'Desa Cugenang',
            'lat' => -6.7865, 'long_decimal' => 107.0863,
            'populasi' => 5000, 'korban_selamat' => 4200,
            'jumlah_orang_sakit' => 320, 'persentase_infrastruktur_rusak' => 78.50,
            'status_isolasi' => true, 'status_aman' => false,
        ]);
        $desaPaceta = Desa::create([
            'id' => 102,
            'id_kabupaten' => $kab->id, 'nama' => 'Desa Pacet',
            'lat' => -6.7550, 'long_decimal' => 107.0500,
            'populasi' => 7000, 'korban_selamat' => 6800,
            'jumlah_orang_sakit' => 150, 'persentase_infrastruktur_rusak' => 45.00,
            'status_isolasi' => false, 'status_aman' => false,
        ]);
        $desaWarungkondang = Desa::create([
            'id' => 103,
            'id_kabupaten' => $kab->id, 'nama' => 'Desa Warungkondang',
            'lat' => -6.8500, 'long_decimal' => 107.0900,
            'populasi' => 8500, 'korban_selamat' => 8400,
            'jumlah_orang_sakit' => 80, 'persentase_infrastruktur_rusak' => 25.00,
            'status_isolasi' => false, 'status_aman' => false,
        ]);
        $desaCilaku = Desa::create([
            'id' => 104,
            'id_kabupaten' => $kab->id, 'nama' => 'Desa Cilaku',
            'lat' => -6.8583, 'long_decimal' => 107.1333,
            'populasi' => 6000, 'korban_selamat' => 6000,
            'jumlah_orang_sakit' => 10, 'persentase_infrastruktur_rusak' => 5.00,
            'status_isolasi' => false, 'status_aman' => true,
        ]);

        // 5. Rute (Adjacency List untuk Matriks Jarak)
        Rute::insert([
            // Depot 1 ke Desa
            ['id_titik_asal' => $depot1->id, 'id_titik_tujuan' => $desaCilaku->id, 'jarak_km' => 5.2, 'status_akses_terbuka' => true],
            ['id_titik_asal' => $depot1->id, 'id_titik_tujuan' => $desaWarungkondang->id, 'jarak_km' => 8.5, 'status_akses_terbuka' => true],
            ['id_titik_asal' => $depot1->id, 'id_titik_tujuan' => $desaCugenang->id, 'jarak_km' => 7.1, 'status_akses_terbuka' => false], // Longsor
            
            // Depot 2 ke Desa
            ['id_titik_asal' => $depot2->id, 'id_titik_tujuan' => $desaPaceta->id, 'jarak_km' => 2.5, 'status_akses_terbuka' => true],
            ['id_titik_asal' => $depot2->id, 'id_titik_tujuan' => $desaCugenang->id, 'jarak_km' => 6.0, 'status_akses_terbuka' => true],
            
            // Inter-Desa (Voronoi connectivity)
            ['id_titik_asal' => $desaCugenang->id, 'id_titik_tujuan' => $desaPaceta->id, 'jarak_km' => 4.2, 'status_akses_terbuka' => true],
            ['id_titik_asal' => $desaWarungkondang->id, 'id_titik_tujuan' => $desaCilaku->id, 'jarak_km' => 3.8, 'status_akses_terbuka' => true],
        ]);

        // 6. Master Bantuan (SKU)
        $mMedis = MasterBantuan::create(['nama' => 'P3K & Obat Darurat', 'kategori' => 'Medis', 'berat_kg' => 5.0, 'volume_m3' => 0.05]);
        $mAir = MasterBantuan::create(['nama' => 'Air Bersih 19L', 'kategori' => 'Air', 'berat_kg' => 19.5, 'volume_m3' => 0.1]);
        $mRansum = MasterBantuan::create(['nama' => 'Sembako Beras 10kg', 'kategori' => 'Ransum', 'berat_kg' => 10.0, 'volume_m3' => 0.08]);
        $mTenda = MasterBantuan::create(['nama' => 'Tenda Peleton', 'kategori' => 'Tenda', 'berat_kg' => 50.0, 'volume_m3' => 0.5]);

        // 7. Stok Pusat
        DB::table('stok_pusat')->insert([
            ['id_pusat' => $depot1->id, 'id_barang' => $mMedis->id, 'total_kuantitas' => 500, 'created_at' => now(), 'updated_at' => now()],
            ['id_pusat' => $depot1->id, 'id_barang' => $mAir->id, 'total_kuantitas' => 2000, 'created_at' => now(), 'updated_at' => now()],
            ['id_pusat' => $depot1->id, 'id_barang' => $mRansum->id, 'total_kuantitas' => 1500, 'created_at' => now(), 'updated_at' => now()],
            ['id_pusat' => $depot1->id, 'id_barang' => $mTenda->id, 'total_kuantitas' => 50, 'created_at' => now(), 'updated_at' => now()],
            
            ['id_pusat' => $depot2->id, 'id_barang' => $mMedis->id, 'total_kuantitas' => 300, 'created_at' => now(), 'updated_at' => now()],
            ['id_pusat' => $depot2->id, 'id_barang' => $mAir->id, 'total_kuantitas' => 1000, 'created_at' => now(), 'updated_at' => now()],
            ['id_pusat' => $depot2->id, 'id_barang' => $mRansum->id, 'total_kuantitas' => 800, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 8. Armada Kendaraan
        ArmadaKendaraan::insert([
            ['id_pusat' => $depot1->id, 'plat_nomor' => 'B 9901 DL', 'max_berat_kg' => 5000.0, 'max_vol_m3' => 20.0, 'status' => 'Available'],
            ['id_pusat' => $depot1->id, 'plat_nomor' => 'B 9902 DL', 'max_berat_kg' => 5000.0, 'max_vol_m3' => 20.0, 'status' => 'Available'],
            ['id_pusat' => $depot2->id, 'plat_nomor' => 'F 8812 XY', 'max_berat_kg' => 2500.0, 'max_vol_m3' => 12.0, 'status' => 'Available'],
            ['id_pusat' => $depot2->id, 'plat_nomor' => 'F 8813 XY', 'max_berat_kg' => 2500.0, 'max_vol_m3' => 12.0, 'status' => 'Available'],
        ]);

        // 9. Demand Kebutuhan Dummy (Draft & Queued) — with target_deadline_jam = 72 / (urgency + 1)
        DemandKebutuhan::create(['id_desa' => $desaCugenang->id, 'id_barang' => $mMedis->id, 'kuantitas' => 50, 'urgency_score' => 95.5, 'target_deadline_jam' => round(72 / (95.5 + 1), 2), 'status' => 'Queued']);
        DemandKebutuhan::create(['id_desa' => $desaCugenang->id, 'id_barang' => $mAir->id, 'kuantitas' => 200, 'urgency_score' => 80.0, 'target_deadline_jam' => round(72 / (80.0 + 1), 2), 'status' => 'Queued']);
        DemandKebutuhan::create(['id_desa' => $desaPaceta->id, 'id_barang' => $mRansum->id, 'kuantitas' => 300, 'urgency_score' => 70.0, 'target_deadline_jam' => round(72 / (70.0 + 1), 2), 'status' => 'Queued']);
        DemandKebutuhan::create(['id_desa' => $desaWarungkondang->id, 'id_barang' => $mTenda->id, 'kuantitas' => 5, 'urgency_score' => 45.0, 'target_deadline_jam' => round(72 / (45.0 + 1), 2), 'status' => 'Draft']);
    }
}
