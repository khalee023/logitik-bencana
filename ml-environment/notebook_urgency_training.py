"""
Disaster Logistics Command Center — ML Urgency Predictor Training
=================================================================
Sections:
    1. Synthetic Data Generation (1000 rows, realistic correlations)
    2. Feature Engineering (normalization, outlier removal)
    3. Train-Test Split (80/20)
    4. Random Forest Regressor (n_estimators=200, max_depth=10)
    5. Evaluation (MAE, RMSE, R²)
    6. Export joblib.dump(model, 'urgency_predictor.pkl')

Features (X):
    - populasi: Village population (500-15000)
    - korban_selamat: Survivors count (fraction of populasi)
    - jumlah_orang_sakit: Sick people count
    - persentase_infrastruktur_rusak: Infrastructure damage % (0-100)
    - status_isolasi: Boolean isolation flag (0/1)

Target (y):
    - urgency_score_label: Urgency score [0, 10]

Formula basis for synthetic label:
    U = 0.2 * norm(korban_selamat/populasi)
      + 0.25 * norm(jumlah_orang_sakit/populasi)
      + 0.3 * norm(persentase_infrastruktur_rusak/100)
      + 0.15 * status_isolasi
      + 0.1 * noise
    Scaled to [0, 10]
"""

import os
import sys
import numpy as np
import pandas as pd
from sklearn.ensemble import RandomForestRegressor
from sklearn.model_selection import train_test_split
from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score
import joblib

# ============================================================================
# 1. SYNTHETIC DATA GENERATION
# ============================================================================

np.random.seed(42)
N = 1000

print("=" * 60)
print("DISASTER LOGISTICS — URGENCY PREDICTOR TRAINING")
print("=" * 60)
print(f"\n[1/6] Generating {N} synthetic village records...")

populasi = np.random.randint(500, 15001, size=N)

# korban_selamat: 50-98% of populasi (more realistic)
survival_rate = np.random.uniform(0.50, 0.98, size=N)
korban_selamat = (populasi * survival_rate).astype(int)

# jumlah_orang_sakit: correlated with populasi and damage
base_sick_rate = np.random.uniform(0.01, 0.15, size=N)
jumlah_orang_sakit = (populasi * base_sick_rate).astype(int)

# persentase_infrastruktur_rusak: 0-100, skewed toward middle
persentase_infrastruktur_rusak = np.clip(
    np.random.beta(2, 3, size=N) * 100, 0, 100
).round(2)

# status_isolasi: ~25% chance of being isolated, more likely with high damage
isolation_prob = 0.1 + 0.4 * (persentase_infrastruktur_rusak / 100)
status_isolasi = (np.random.uniform(size=N) < isolation_prob).astype(int)

# ============================================================================
# Urgency Score Label (synthetic ground truth)
# ============================================================================
# Weighted multi-factor formula with realistic noise
survivor_ratio = korban_selamat / populasi
sick_ratio = jumlah_orang_sakit / populasi
damage_ratio = persentase_infrastruktur_rusak / 100

urgency_raw = (
    0.20 * survivor_ratio        # Higher survival → more mouths to feed
    + 0.25 * sick_ratio           # Sick people need urgent medical aid
    + 0.30 * damage_ratio         # Infrastructure damage impedes logistics
    + 0.15 * status_isolasi       # Isolation multiplier
    + 0.10 * np.random.normal(0.5, 0.1, size=N)  # Noise factor
)

# Normalize to [0, 10]
urgency_min = urgency_raw.min()
urgency_max = urgency_raw.max()
urgency_score_label = np.clip(
    ((urgency_raw - urgency_min) / (urgency_max - urgency_min)) * 10,
    0, 10
).round(4)

df = pd.DataFrame({
    'populasi': populasi,
    'korban_selamat': korban_selamat,
    'jumlah_orang_sakit': jumlah_orang_sakit,
    'persentase_infrastruktur_rusak': persentase_infrastruktur_rusak,
    'status_isolasi': status_isolasi,
    'urgency_score_label': urgency_score_label,
})

# Save synthetic dataset
csv_path = os.path.join(os.path.dirname(__file__), 'synthetic_desa_logistics.csv')
df.to_csv(csv_path, index=False)
print(f"   -> Dataset saved: {csv_path}")
print(f"   -> Shape: {df.shape}")
print(f"   -> Urgency score range: [{urgency_score_label.min():.2f}, {urgency_score_label.max():.2f}]")
print(df.describe().round(2))

# ============================================================================
# 2. FEATURE ENGINEERING
# ============================================================================

print(f"\n[2/6] Feature engineering...")

feature_cols = [
    'populasi',
    'korban_selamat',
    'jumlah_orang_sakit',
    'persentase_infrastruktur_rusak',
    'status_isolasi',
]
target_col = 'urgency_score_label'

X = df[feature_cols].copy()
y = df[target_col].copy()

# Remove outliers (IQR-based on target)
Q1 = y.quantile(0.01)
Q3 = y.quantile(0.99)
mask = (y >= Q1) & (y <= Q3)
X = X[mask]
y = y[mask]

print(f"   -> After outlier removal: {len(X)} records")

# ============================================================================
# 3. TRAIN-TEST SPLIT
# ============================================================================

print(f"\n[3/6] Splitting train/test (80/20)...")

X_train, X_test, y_train, y_test = train_test_split(
    X, y, test_size=0.2, random_state=42
)

print(f"   -> Train set: {len(X_train)} records")
print(f"   -> Test set: {len(X_test)} records")

# ============================================================================
# 4. RANDOM FOREST REGRESSOR
# ============================================================================

print(f"\n[4/6] Training RandomForestRegressor(n_estimators=200, max_depth=10)...")

model = RandomForestRegressor(
    n_estimators=200,
    max_depth=10,
    min_samples_split=5,
    min_samples_leaf=3,
    random_state=42,
    n_jobs=-1,
)

model.fit(X_train, y_train)
print("   -> Training complete!")

# Feature importances
importances = dict(zip(feature_cols, model.feature_importances_))
print("\n   Feature Importances:")
for feat, imp in sorted(importances.items(), key=lambda x: x[1], reverse=True):
    print(f"     {feat:40s} {imp:.4f}")

# ============================================================================
# 5. EVALUATION
# ============================================================================

print(f"\n[5/6] Evaluating model...")

y_pred = model.predict(X_test)
y_pred_clipped = np.clip(y_pred, 0, 10)

mae = mean_absolute_error(y_test, y_pred_clipped)
rmse = np.sqrt(mean_squared_error(y_test, y_pred_clipped))
r2 = r2_score(y_test, y_pred_clipped)

print(f"   MAE:  {mae:.4f}")
print(f"   RMSE: {rmse:.4f}")
print(f"   R²:   {r2:.4f}")

# ============================================================================
# 6. EXPORT MODEL
# ============================================================================

print(f"\n[6/6] Exporting model artifact...")

# Export to or-microservice/app/services/
pkl_path = os.path.join(
    os.path.dirname(__file__), '..', 'or-microservice', 'app', 'services', 'urgency_predictor.pkl'
)
pkl_path = os.path.abspath(pkl_path)

os.makedirs(os.path.dirname(pkl_path), exist_ok=True)
joblib.dump(model, pkl_path)

print(f"   -> Model exported: {pkl_path}")
print(f"   -> File size: {os.path.getsize(pkl_path) / 1024:.1f} KB")

print("\n" + "=" * 60)
print("TRAINING COMPLETE")
print("=" * 60)
