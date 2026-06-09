"""
UrgencyPredictor — ML Inference Service
========================================
Loads a pre-trained Random Forest model (.pkl) and provides
inference for village urgency scoring.

Features (input DataFrame columns):
    - populasi (int)
    - korban_selamat (int)
    - jumlah_orang_sakit (int)
    - persentase_infrastruktur_rusak (float, 0-100)
    - status_isolasi (bool/int, 0 or 1)

Output:
    - urgency_score per village, clipped to [0, 10]
"""

import os
import logging
from typing import Optional

import numpy as np
import pandas as pd

logger = logging.getLogger(__name__)

# Feature column order must match training
FEATURE_COLUMNS = [
    "populasi",
    "korban_selamat",
    "jumlah_orang_sakit",
    "persentase_infrastruktur_rusak",
    "status_isolasi",
]

# Fallback weights for deterministic scoring when model is unavailable
FALLBACK_WEIGHTS = {
    "populasi": 0.05,
    "korban_selamat": 0.20,
    "jumlah_orang_sakit": 0.30,
    "persentase_infrastruktur_rusak": 0.30,
    "status_isolasi": 0.15,
}


class UrgencyPredictor:
    """
    ML-based urgency predictor with graceful fallback.
    
    Usage:
        predictor = UrgencyPredictor()
        df = pd.DataFrame([{...features...}])
        scores = predictor.predict(df)
    """

    def __init__(self, model_path: Optional[str] = None):
        """
        Initialize predictor by loading the .pkl model from disk.
        
        Args:
            model_path: Path to urgency_predictor.pkl. Defaults to
                        same directory as this file.
        """
        self.model = None
        self.model_loaded = False

        if model_path is None:
            model_path = os.path.join(
                os.path.dirname(__file__), "urgency_predictor.pkl"
            )

        try:
            import joblib
            self.model = joblib.load(model_path)
            self.model_loaded = True
            logger.info(f"UrgencyPredictor: Model loaded from {model_path}")
        except FileNotFoundError:
            logger.warning(
                f"UrgencyPredictor: Model file not found at {model_path}. "
                f"Falling back to deterministic scoring."
            )
        except Exception as e:
            logger.error(
                f"UrgencyPredictor: Failed to load model: {e}. "
                f"Falling back to deterministic scoring."
            )

    def predict(self, df: pd.DataFrame) -> np.ndarray:
        """
        Predict urgency scores for a batch of villages.
        
        Args:
            df: DataFrame with columns matching FEATURE_COLUMNS.
                Missing columns will be filled with 0.
        
        Returns:
            numpy array of urgency scores clipped to [0, 10].
        """
        # Ensure all required columns exist
        for col in FEATURE_COLUMNS:
            if col not in df.columns:
                df[col] = 0

        X = df[FEATURE_COLUMNS].copy()

        # Convert boolean to int
        if X["status_isolasi"].dtype == bool:
            X["status_isolasi"] = X["status_isolasi"].astype(int)

        if self.model is not None and self.model_loaded:
            # ML prediction
            raw_scores = self.model.predict(X)
        else:
            # Deterministic fallback: weighted normalized sum
            raw_scores = self._fallback_predict(X)

        # Clip to valid range [0, 10]
        return np.clip(raw_scores, 0, 10).round(4)

    def _fallback_predict(self, X: pd.DataFrame) -> np.ndarray:
        """
        Deterministic fallback scoring when ML model is unavailable.
        Uses weighted normalization of features.
        """
        scores = np.zeros(len(X))

        # Normalize each feature to [0, 1] range
        for col, weight in FALLBACK_WEIGHTS.items():
            values = X[col].values.astype(float)
            if col == "persentase_infrastruktur_rusak":
                normalized = values / 100.0
            elif col == "status_isolasi":
                normalized = values
            else:
                max_val = values.max() if values.max() > 0 else 1
                normalized = values / max_val
            scores += weight * normalized

        # Scale to [0, 10]
        return scores * 10
