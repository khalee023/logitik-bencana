import json
from app.services.or_solver import execute_cvrptw_solver

payload = {
    "depots": [{"id_pusat": 1, "lat": 0.0, "lng": 0.0}, {"id_pusat": 2, "lat": 0.0, "lng": 0.0}],
    "nodes": [
        {"id_desa": 101, "lat": 0.0, "lng": 0.0, "berat_demand": 2000.0, "vol_demand": 5.0, "urgency_score": 100.0, "window_start": 0, "window_end": 1440}
    ],
    "fleet": [
        {"id_armada": 4, "id_pusat": 2, "max_berat": 2500.0, "max_vol": 10.0}
    ],
    "distance_matrix": [
        [0.0, 999999.0, 999999.0],
        [999999.0, 0.0, 6.0],
        [999999.0, 6.0, 0.0]
    ],
    "time_matrix": [
        [0, 9999, 9999],
        [9999, 0, 9],
        [9999, 9, 0]
    ]
}

res = execute_cvrptw_solver(payload)
print(json.dumps(res, indent=2))
