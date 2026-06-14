<?php

namespace App\Services;

use App\Models\Desa;
use App\Models\DemandKebutuhan;
use App\Models\MasterBantuan;
use Illuminate\Support\Facades\DB;

class BurnRateService
{
    /**
     * Koefisien bobot kepentingan absolut komoditas.
     * W_medis (40) > W_air (30) > W_ransum (20) > W_tenda (10)
     */
    private const CATEGORY_WEIGHTS = [
        'Medis'  => 40.00,
        'Air'    => 30.00,
        'Ransum' => 20.00,
        'Tenda'  => 10.00,
    ];

    /**
     * Epsilon — pengaman matematis untuk division-by-zero guard.
     */
    private const EPSILON = 1e-5;

    /**
     * Menghitung skor urgensi deterministik untuk satu desa.
     *
     * Formulasi (normalized to 0-10 scale to match ML UrgencyPredictor):
     *   U_i = min(10.00, Σ_{m ∈ M} W_m · (korban_selamat_i / (total_kuantitas_im + ε)) / 10)
     *
     * @param  int  $korbanSelamat  Jumlah korban selamat
     * @param  array  $stokPerKategori  ['Medis' => qty, 'Air' => qty, ...]
     * @return float  Skor urgensi 0.00 - 10.00
     */
    public function calculateScore(Desa $desa, array $stokPerKategori): float
    {
        // Jika tidak ada masalah sama sekali dan desa aman, urgency 0
        if ($desa->status_aman && $desa->korban_selamat == 0 && $desa->jumlah_orang_sakit == 0 && $desa->persentase_infrastruktur_rusak == 0) {
            return 0.00;
        }

        $totalScore = 0.00;
        
        // Base severity dari metrik desa
        $baseSeverity = ($desa->korban_selamat * 0.05) 
                      + ($desa->jumlah_orang_sakit * 0.1) 
                      + ($desa->persentase_infrastruktur_rusak * 0.05);

        foreach (self::CATEGORY_WEIGHTS as $kategori => $weight) {
            $kuantitas = $stokPerKategori[$kategori] ?? 0;
            // Jika stok 0, pembaginya 1 agar tidak meledak ke jutaan
            $ratio = $baseSeverity / max(1, $kuantitas);
            $totalScore += ($weight / 100.0) * $ratio;
        }

        // Tambahan bobot jika desa terisolasi
        if ($desa->status_isolasi) {
            $totalScore += 2.0;
        }

        return min(10.00, round($totalScore, 2));
    }

    /**
     * Menghitung dan memperbarui urgency_score untuk semua demand di desa tertentu.
     *
     * @param  int  $idDesa
     * @return float  Skor urgensi yang dihitung
     */
    public function updateDesaUrgencyScores(int $idDesa): float
    {
        $desa = Desa::findOrFail($idDesa);

        // Hitung stok tersedia per kategori untuk desa ini
        // (berdasarkan demand yang sudah terpenuhi)
        $stokPerKategori = [];
        foreach (self::CATEGORY_WEIGHTS as $kategori => $weight) {
            $totalTersedia = DemandKebutuhan::where('id_desa', $idDesa)
                ->where('status', 'Fulfilled')
                ->whereHas('barang', function ($query) use ($kategori) {
                    $query->where('kategori', $kategori);
                })
                ->sum('kuantitas');
            $stokPerKategori[$kategori] = $totalTersedia;
        }

        $score = $this->calculateScore($desa, $stokPerKategori);

        // Update semua demand aktif (Draft/Queued) dengan skor baru
        DemandKebutuhan::where('id_desa', $idDesa)
            ->whereIn('status', ['Draft', 'Queued'])
            ->update(['urgency_score' => $score]);

        return $score;
    }
}
