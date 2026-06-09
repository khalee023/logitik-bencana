"""
Burn-Rate Priority Scoring
Menghitung skor urgensi deterministik untuk setiap desa
berdasarkan laju konsumsi logistik (burn-rate).

Formulasi:
    U_i = min(100.00, Σ_{m ∈ M} W_m · (korban_selamat_i / (total_kuantitas_im + ε)))

Dimana:
    M = {Medis, Air, Ransum, Tenda}
    W_medis = 40.00, W_air = 30.00, W_ransum = 20.00, W_tenda = 10.00
    ε = 1e-5 (division-by-zero guard)
"""

from typing import Any, Dict, List, Optional

# Koefisien bobot kepentingan absolut komoditas (hierarki ketat)
CATEGORY_WEIGHTS: Dict[str, float] = {
    "Medis": 40.00,
    "Air": 30.00,
    "Ransum": 20.00,
    "Tenda": 10.00,
}

# Konstanta pengaman matematis untuk mencegah division by zero
EPSILON: float = 1e-5


def calculate_urgency_score(
    korban_selamat: int,
    stok_per_kategori: Dict[str, float],
) -> float:
    """
    Menghitung skor urgensi untuk satu desa.

    Args:
        korban_selamat: Jumlah korban selamat di desa
        stok_per_kategori: Dictionary {kategori: total_kuantitas}
            Contoh: {"Medis": 50, "Air": 200, "Ransum": 100, "Tenda": 30}

    Returns:
        Skor urgensi 0.00 - 100.00
    """
    if korban_selamat <= 0:
        return 0.00

    total_score = 0.00

    for kategori, weight in CATEGORY_WEIGHTS.items():
        kuantitas = stok_per_kategori.get(kategori, 0)
        # Rumus: W_m * (korban_selamat / (kuantitas + ε))
        ratio = korban_selamat / (kuantitas + EPSILON)
        total_score += weight * ratio

    # Clamp ke batas atas 100.00
    return min(100.00, round(total_score, 2))


def calculate_batch_urgency_scores(
    desa_data: List[Dict[str, Any]],
) -> List[Dict[str, Any]]:
    """
    Menghitung skor urgensi untuk batch desa sekaligus.

    Args:
        desa_data: List of dicts, masing-masing berisi:
            - id_desa (int)
            - korban_selamat (int)
            - stok_per_kategori (dict): {"Medis": qty, "Air": qty, ...}

    Returns:
        List of dicts: [{"id_desa": int, "urgency_score": float}, ...]
    """
    results = []

    for desa in desa_data:
        id_desa = desa["id_desa"]
        korban = desa.get("korban_selamat", 0)
        stok = desa.get("stok_per_kategori", {})

        score = calculate_urgency_score(korban, stok)

        results.append({
            "id_desa": id_desa,
            "urgency_score": score,
        })

    return results
