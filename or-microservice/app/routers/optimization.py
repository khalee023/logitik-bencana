"""
FastAPI Router: Optimization & ML Prediction Endpoints
Mengisolasi eksekusi solver CPU-bound menggunakan ProcessPoolExecutor.
Menyediakan endpoint prediksi urgensi melalui ML model.
"""

from fastapi import APIRouter, HTTPException, status
from pydantic import BaseModel, Field
from typing import Dict, List, Optional
import concurrent.futures
import multiprocessing
import pandas as pd

from app.services.or_solver import execute_cvrptw_solver

router = APIRouter(prefix="/api", tags=["Optimization"])

# Deteksi kapasitas pemrosesan perangkat keras
CPU_CORES = multiprocessing.cpu_count()
process_pool = concurrent.futures.ProcessPoolExecutor(
    max_workers=max(1, CPU_CORES - 1)
)


class DemandNode(BaseModel):
    """Representasi simpul permintaan (desa) untuk solver."""
    id_desa: int
    lat: float
    lng: float
    berat_demand: float = Field(ge=0, description="Total berat permintaan (kg)")
    vol_demand: float = Field(ge=0, description="Total volume permintaan (m³)")
    urgency_score: float = Field(ge=0, le=100, description="Skor urgensi 0-100")
    window_start: int = Field(ge=0, description="Awal jendela waktu (menit)")
    window_end: int = Field(ge=0, description="Akhir jendela waktu (menit)")
    demand_ids: List[int] = Field(default_factory=list, description="ID demand_kebutuhan terkait")


class VehicleNode(BaseModel):
    """Representasi armada kendaraan."""
    id_armada: int
    id_pusat: int = Field(description="ID pusat distribusi armada ini")
    max_berat: float = Field(gt=0, description="Kapasitas berat maksimum (kg)")
    max_vol: float = Field(gt=0, description="Kapasitas volume maksimum (m³)")


class DepotNode(BaseModel):
    """Representasi simpul depot (pusat distribusi)."""
    id_pusat: int
    lat: float
    lng: float


class OptimizationPayload(BaseModel):
    """Payload lengkap untuk memicu kalkulasi CVRPTW."""
    depots: List[DepotNode]
    nodes: List[DemandNode]
    fleet: List[VehicleNode]
    distance_matrix: List[List[float]]
    time_matrix: List[List[int]]
    speed_kmh: float = Field(default=40.0, description="Kecepatan rata-rata kendaraan (km/jam)")
    alpha: float = Field(default=1.0, description="Bobot jarak dalam fungsi tujuan")
    beta: float = Field(default=0.01, description="Bobot penalti urgensi dalam fungsi tujuan")


class VillageFeatures(BaseModel):
    """Fitur desa untuk prediksi urgensi ML."""
    id: int
    populasi: int = Field(ge=0)
    korban_selamat: int = Field(ge=0)
    jumlah_orang_sakit: int = Field(ge=0)
    persentase_infrastruktur_rusak: float = Field(ge=0, le=100)
    status_isolasi: bool = False


class PredictUrgencyRequest(BaseModel):
    """Payload untuk prediksi urgensi batch."""
    villages: List[VillageFeatures]


class PredictUrgencyResponse(BaseModel):
    """Respons prediksi urgensi."""
    urgency_scores: Dict[int, float]


class RouteStop(BaseModel):
    """Satu titik singgah dalam rute kendaraan."""
    node_index: int
    id_desa: Optional[int] = None
    arrival_time: int
    departure_time: int
    load_berat: float
    load_vol: float


class VehicleRoute(BaseModel):
    """Rute lengkap satu kendaraan."""
    id_armada: int
    vehicle_index: int
    stops: List[RouteStop]
    total_distance: float
    total_time: int
    utilization_berat_pct: float
    utilization_vol_pct: float


class OptimizationResult(BaseModel):
    """Hasil kalkulasi optimasi CVRPTW."""
    status: str
    total_distance: float
    total_vehicles_used: int
    routes: List[VehicleRoute]
    unserved_nodes: List[int]
    solver_wall_time_ms: int


def compute_optimization_job(payload_data: dict) -> dict:
    """Fungsi pembungkus steril yang dieksekusi di dalam ProcessPoolExecutor."""
    try:
        result = execute_cvrptw_solver(payload_data)
        return result
    except Exception as e:
        return {"status": "ERROR", "message": str(e)}


@router.post(
    "/predict-urgency",
    response_model=PredictUrgencyResponse,
    status_code=status.HTTP_200_OK,
    summary="Prediksi Skor Urgensi via ML",
    description="Menerima fitur desa dan mengembalikan skor urgensi [0, 10] per desa.",
)
def predict_urgency(payload: PredictUrgencyRequest):
    """
    Endpoint prediksi urgensi menggunakan trained Random Forest model.
    Fallback ke scoring deterministik jika model tidak tersedia.
    """
    from app.main import predictor_instance

    if predictor_instance is None:
        from app.services.urgency_predictor import UrgencyPredictor
        predictor_instance_local = UrgencyPredictor()
    else:
        predictor_instance_local = predictor_instance

    # Build DataFrame from village features
    records = []
    for v in payload.villages:
        records.append({
            "populasi": v.populasi,
            "korban_selamat": v.korban_selamat,
            "jumlah_orang_sakit": v.jumlah_orang_sakit,
            "persentase_infrastruktur_rusak": v.persentase_infrastruktur_rusak,
            "status_isolasi": int(v.status_isolasi),
        })

    df = pd.DataFrame(records)
    scores = predictor_instance_local.predict(df)

    # Map village IDs to scores
    urgency_scores = {
        v.id: round(float(score), 4)
        for v, score in zip(payload.villages, scores)
    }

    return PredictUrgencyResponse(urgency_scores=urgency_scores)


@router.post(
    "/optimize",
    response_model=OptimizationResult,
    status_code=status.HTTP_200_OK,
    summary="Eksekusi Optimasi CVRPTW Global",
    description="Menerima payload data logistik dan mengembalikan solusi rute optimal.",
)
def trigger_global_optimization(payload: OptimizationPayload):
    """
    Endpoint sinkron (def, bukan async def) agar FastAPI otomatis
    mengeksekusi di thread pool terpisah. Komputasi berat didelegasikan
    ke ProcessPoolExecutor untuk melewati limitasi GIL Python.
    """
    # Konversi model Pydantic ke kamus data primitif untuk isolasi proses
    serialized_payload = payload.model_dump()

    # Mendelegasikan komputasi berat ke worker pool proses terpisah
    future = process_pool.submit(compute_optimization_job, serialized_payload)

    try:
        # Menunggu hasil eksekusi proses eksternal dengan batas waktu 30 detik
        optimization_result = future.result(timeout=30.0)
    except concurrent.futures.TimeoutError:
        raise HTTPException(
            status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
            detail="Optimization computation exceeded maximum allowable execution window (30s Timeout)",
        )

    if isinstance(optimization_result, dict) and optimization_result.get("status") == "ERROR":
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail=f"Solver execution failed: {optimization_result.get('message', 'Unknown error')}",
        )

    return optimization_result
