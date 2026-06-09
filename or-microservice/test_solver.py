import json
from app.services.or_solver import execute_cvrptw_solver

payload = {
    "depots": [{"id_pusat": 1, "lat": 0.0, "lng": 0.0}, {"id_pusat": 2, "lat": 0.0, "lng": 0.0}],
    "nodes": [
        {"id_desa": 101, "lat": 0.0, "lng": 0.0, "berat_demand": 2400.0, "vol_demand": 9.0, "urgency_score": 100.0, "window_start": 0, "window_end": 1440},
        {"id_desa": 102, "lat": 0.0, "lng": 0.0, "berat_demand": 2400.0, "vol_demand": 9.0, "urgency_score": 70.0, "window_start": 0, "window_end": 1440},
        {"id_desa": 103, "lat": 0.0, "lng": 0.0, "berat_demand": 250.0, "vol_demand": 5.0, "urgency_score": 45.0, "window_start": 0, "window_end": 1440}
    ],
    "fleet": [
        {"id_armada": 1, "id_pusat": 1, "max_berat": 5000.0, "max_vol": 20.0},
        {"id_armada": 2, "id_pusat": 1, "max_berat": 5000.0, "max_vol": 20.0},
        {"id_armada": 3, "id_pusat": 2, "max_berat": 2500.0, "max_vol": 10.0},
        {"id_armada": 4, "id_pusat": 2, "max_berat": 2500.0, "max_vol": 10.0}
    ],
    "distance_matrix": [
        [0.0, 999999.0, 999999.0, 999999.0, 8.5],
        [999999.0, 0.0, 6.0, 2.5, 999999.0],
        [999999.0, 6.0, 0.0, 4.2, 999999.0],
        [999999.0, 2.5, 4.2, 0.0, 999999.0],
        [8.5, 999999.0, 999999.0, 999999.0, 0.0]
    ],
    "time_matrix": [
        [0, 9999, 9999, 9999, 13],
        [9999, 0, 9, 4, 9999],
        [9999, 9, 0, 7, 9999],
        [9999, 4, 7, 0, 9999],
        [13, 9999, 9999, 9999, 0]
    ]
}

res = execute_cvrptw_solver(payload)
print(json.dumps(res, indent=2))
