"""
Disaster Logistics Command Center — FastAPI Microservice
Subsistem optimasi untuk CVRPTW dengan Google OR-Tools.
Includes ML-based urgency prediction via UrgencyPredictor.
"""

from contextlib import asynccontextmanager
import logging

from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from app.routers import optimization
from app.services.urgency_predictor import UrgencyPredictor

logger = logging.getLogger(__name__)

# Global reference to the predictor instance
predictor_instance: UrgencyPredictor = None


@asynccontextmanager
async def lifespan(app: FastAPI):
    """
    Lifespan event handler: pre-load ML model into memory at startup.
    This avoids cold-start latency on the first /predict-urgency request.
    """
    global predictor_instance
    logger.info("Loading UrgencyPredictor model into memory...")
    predictor_instance = UrgencyPredictor()
    logger.info(f"Model loaded: {predictor_instance.model_loaded}")
    yield
    # Cleanup on shutdown
    predictor_instance = None
    logger.info("UrgencyPredictor model unloaded.")


app = FastAPI(
    title="Disaster Logistics Optimization Microservice",
    description="CVRPTW solver menggunakan Google OR-Tools + ML urgency prediction untuk optimasi rute distribusi bantuan bencana.",
    version="1.0.0",
    lifespan=lifespan,
)

# CORS — izinkan komunikasi dari Laravel monolith
app.add_middleware(
    CORSMiddleware,
    allow_origins=["http://localhost:8000", "http://127.0.0.1:8000"],
    allow_credentials=True,
    allow_methods=["POST", "GET"],
    allow_headers=["*"],
)

app.include_router(optimization.router)


@app.get("/health", tags=["System"])
async def health_check():
    """Health check endpoint with model status."""
    return {
        "status": "OK",
        "service": "or-microservice",
        "model_loaded": predictor_instance.model_loaded if predictor_instance else False,
    }
