"""
Google OR-Tools CVRPTW Solver
Menyelesaikan Capacitated Vehicle Routing Problem with Time Windows
dengan modifikasi urgensi untuk distribusi bantuan bencana.

Formulasi:
    Min Z = α·Σ c_ij·x_ij^k + β·Σ (100 - U_i)·s_i^k

Constraints:
    - Kapasitas berat: Σ d_i^berat ≤ Q_berat  ∀k
    - Kapasitas volume: Σ d_i^vol ≤ Q_vol  ∀k
    - Jendela waktu: e_i ≤ s_i^k ≤ l_i  ∀i,k
"""

import time
from typing import Any, Dict, List

from ortools.constraint_solver import pywrapcp, routing_enums_pb2


def execute_cvrptw_solver(payload: Dict[str, Any]) -> Dict[str, Any]:
    """
    Fungsi utama solver CVRPTW.

    Args:
        payload: Dictionary berisi depots, nodes, fleet, distance_matrix,
                 time_matrix, speed_kmh, alpha, beta.

    Returns:
        Dictionary berisi status, rute optimal, ETA, dan metrik utilisasi.
    """
    start_time = time.perf_counter()

    depots = payload["depots"]
    nodes = payload["nodes"]
    fleet = payload["fleet"]
    distance_matrix = payload["distance_matrix"]
    time_matrix = payload["time_matrix"]
    alpha = payload.get("alpha", 1.0)
    beta = payload.get("beta", 0.01)

    num_depots = len(depots)
    num_nodes = len(nodes)
    num_vehicles = len(fleet)
    total_locations = num_depots + num_nodes

    # Validasi matriks
    if len(distance_matrix) != total_locations or any(
        len(row) != total_locations for row in distance_matrix
    ):
        return {
            "status": "ERROR",
            "message": f"Distance matrix dimension mismatch. Expected {total_locations}x{total_locations}.",
        }

    # === SETUP OR-TOOLS ROUTING MODEL ===

    # Map id_pusat ke index depot di dalam list locations
    depot_index_map = {depot["id_pusat"]: idx for idx, depot in enumerate(depots)}

    starts = []
    ends = []
    for v in fleet:
        # Default fallback to 0 if something is wrong
        depot_idx = depot_index_map.get(v.get("id_pusat", depots[0]["id_pusat"]), 0)
        starts.append(depot_idx)
        ends.append(depot_idx)

    manager = pywrapcp.RoutingIndexManager(
        total_locations,  # jumlah total lokasi (depots + demand nodes)
        num_vehicles,     # jumlah kendaraan
        starts,           # node awal setiap kendaraan
        ends              # node akhir setiap kendaraan
    )

    routing = pywrapcp.RoutingModel(manager)

    # --- Distance Callback ---
    def distance_callback(from_index, to_index):
        from_node = manager.IndexToNode(from_index)
        to_node = manager.IndexToNode(to_index)
        # Skala ke integer (OR-Tools bekerja dengan integer)
        return int(distance_matrix[from_node][to_node] * 1000)

    transit_callback_index = routing.RegisterTransitCallback(distance_callback)
    routing.SetArcCostEvaluatorOfAllVehicles(transit_callback_index)

    # --- Dimensi Jarak (untuk tracking total jarak) ---
    routing.AddDimension(
        transit_callback_index,
        0,           # no slack
        3000000,     # max jarak per kendaraan (3000 km dalam satuan meter)
        True,        # start cumul to zero
        "Distance",
    )

    # --- Time Callback ---
    def time_callback(from_index, to_index):
        from_node = manager.IndexToNode(from_index)
        to_node = manager.IndexToNode(to_index)
        return time_matrix[from_node][to_node]

    time_callback_index = routing.RegisterTransitCallback(time_callback)

    # --- Dimensi Waktu (untuk time windows) ---
    max_time = 1440  # 24 jam dalam menit
    routing.AddDimension(
        time_callback_index,
        30,          # allowed waiting time (slack) di setiap node (menit)
        max_time,    # maximum time per vehicle route
        False,       # don't force start cumul to zero (memungkinkan keberangkatan terjadwal)
        "Time",
    )
    time_dimension = routing.GetDimensionOrDie("Time")

    # Terapkan jendela waktu untuk setiap node
    for location_idx in range(total_locations):
        if location_idx < num_depots:
            # Depot: jendela waktu penuh (0 sampai max)
            index = manager.NodeToIndex(location_idx)
            time_dimension.CumulVar(index).SetRange(0, max_time)
        else:
            # Demand node
            node_data = nodes[location_idx - num_depots]
            index = manager.NodeToIndex(location_idx)
            time_dimension.CumulVar(index).SetRange(
                node_data["window_start"],
                node_data["window_end"],
            )

    # Minimize waiting time di depot untuk setiap kendaraan
    for vehicle_id in range(num_vehicles):
        start_index = routing.Start(vehicle_id)
        time_dimension.CumulVar(start_index).SetRange(0, max_time)
        routing.AddVariableMinimizedByFinalizer(
            time_dimension.CumulVar(routing.Start(vehicle_id))
        )
        routing.AddVariableMinimizedByFinalizer(
            time_dimension.CumulVar(routing.End(vehicle_id))
        )

    # --- Dimensi Kapasitas Berat ---
    def demand_berat_callback(from_index):
        from_node = manager.IndexToNode(from_index)
        if from_node < num_depots:
            return 0  # depot tidak punya demand
        node_data = nodes[from_node - num_depots]
        return int(node_data["berat_demand"] * 100)  # skala ke integer (x100)

    demand_berat_callback_index = routing.RegisterUnaryTransitCallback(
        demand_berat_callback
    )
    routing.AddDimensionWithVehicleCapacity(
        demand_berat_callback_index,
        0,  # no slack
        [int(v["max_berat"] * 100) for v in fleet],  # kapasitas per kendaraan
        True,  # start cumul to zero
        "CapacityBerat",
    )

    # --- Dimensi Kapasitas Volume ---
    def demand_vol_callback(from_index):
        from_node = manager.IndexToNode(from_index)
        if from_node < num_depots:
            return 0
        node_data = nodes[from_node - num_depots]
        return int(node_data["vol_demand"] * 10000)  # skala ke integer (x10000)

    demand_vol_callback_index = routing.RegisterUnaryTransitCallback(
        demand_vol_callback
    )
    routing.AddDimensionWithVehicleCapacity(
        demand_vol_callback_index,
        0,
        [int(v["max_vol"] * 10000) for v in fleet],
        True,
        "CapacityVolume",
    )

    # --- Penalti Urgensi ---
    # Berikan disjunction (izin untuk didrop) ke semua depot dengan penalti 0
    # agar depot yang tidak dipakai oleh armada mana pun tidak memaksa NO_SOLUTION
    for depot_idx in range(num_depots):
        routing.AddDisjunction([manager.NodeToIndex(depot_idx)], 0)

    # Node dengan urgency tinggi mendapat penalti besar jika di-drop
    # Ini memastikan solver memprioritaskan node berurgensi tinggi
    for node_idx in range(num_nodes):
        location_idx = num_depots + node_idx
        routing_index = manager.NodeToIndex(location_idx)
        urgency = nodes[node_idx]["urgency_score"]

        # Penalti drop: semakin tinggi urgensi, semakin mahal untuk diabaikan
        # Skala: urgency 100 → penalti 100000, urgency 0 → penalti 1000
        drop_penalty = int(1000 + urgency * 990)
        routing.AddDisjunction([routing_index], drop_penalty)

    # === SOLVER PARAMETERS ===
    search_parameters = pywrapcp.DefaultRoutingSearchParameters()
    search_parameters.first_solution_strategy = (
        routing_enums_pb2.FirstSolutionStrategy.LOCAL_CHEAPEST_INSERTION
    )
    search_parameters.local_search_metaheuristic = (
        routing_enums_pb2.LocalSearchMetaheuristic.GUIDED_LOCAL_SEARCH
    )
    search_parameters.time_limit.FromSeconds(15)  # 15 detik batas solver

    # === SOLVE ===
    solution = routing.SolveWithParameters(search_parameters)

    wall_time_ms = int((time.perf_counter() - start_time) * 1000)

    if not solution:
        return {
            "status": "NO_SOLUTION",
            "total_distance": 0,
            "total_vehicles_used": 0,
            "routes": [],
            "unserved_nodes": list(range(num_nodes)),
            "solver_wall_time_ms": wall_time_ms,
        }

    # === EXTRACT SOLUTION ===
    routes = []
    total_distance_all = 0
    served_nodes = set()

    time_dimension = routing.GetDimensionOrDie("Time")
    capacity_berat_dim = routing.GetDimensionOrDie("CapacityBerat")
    capacity_vol_dim = routing.GetDimensionOrDie("CapacityVolume")

    for vehicle_id in range(num_vehicles):
        index = routing.Start(vehicle_id)
        stops = []
        route_distance = 0

        while not routing.IsEnd(index):
            node_index = manager.IndexToNode(index)
            time_var = time_dimension.CumulVar(index)
            berat_var = capacity_berat_dim.CumulVar(index)
            vol_var = capacity_vol_dim.CumulVar(index)

            id_desa = None
            if node_index >= num_depots:
                demand_node = nodes[node_index - num_depots]
                id_desa = demand_node["id_desa"]
                served_nodes.add(node_index - num_depots)

            stops.append({
                "node_index": node_index,
                "id_desa": id_desa,
                "arrival_time": solution.Min(time_var),
                "departure_time": solution.Max(time_var),
                "load_berat": solution.Value(berat_var) / 100.0,
                "load_vol": solution.Value(vol_var) / 10000.0,
            })

            previous_index = index
            index = solution.Value(routing.NextVar(index))
            route_distance += routing.GetArcCostForVehicle(
                previous_index, index, vehicle_id
            )

        # Tambahkan stop terakhir (kembali ke depot)
        node_index = manager.IndexToNode(index)
        time_var = time_dimension.CumulVar(index)
        berat_var = capacity_berat_dim.CumulVar(index)
        vol_var = capacity_vol_dim.CumulVar(index)
        stops.append({
            "node_index": node_index,
            "id_desa": None,
            "arrival_time": solution.Min(time_var),
            "departure_time": solution.Max(time_var),
            "load_berat": solution.Value(berat_var) / 100.0,
            "load_vol": solution.Value(vol_var) / 10000.0,
        })

        # Hanya tambahkan rute jika kendaraan benar-benar melayani node
        if len(stops) > 2:  # lebih dari depot_start + depot_end
            route_distance_km = route_distance / 1000.0
            total_distance_all += route_distance_km

            total_time = (
                stops[-1]["arrival_time"] - stops[0]["departure_time"]
            )

            # Hitung utilisasi muatan
            max_berat_load = max(s["load_berat"] for s in stops)
            max_vol_load = max(s["load_vol"] for s in stops)

            routes.append({
                "id_armada": fleet[vehicle_id]["id_armada"],
                "vehicle_index": vehicle_id,
                "stops": stops,
                "total_distance": round(route_distance_km, 2),
                "total_time": total_time,
                "utilization_berat_pct": round(
                    (max_berat_load / fleet[vehicle_id]["max_berat"]) * 100, 1
                )
                if fleet[vehicle_id]["max_berat"] > 0
                else 0,
                "utilization_vol_pct": round(
                    (max_vol_load / fleet[vehicle_id]["max_vol"]) * 100, 1
                )
                if fleet[vehicle_id]["max_vol"] > 0
                else 0,
            })

    # Identifikasi node yang tidak terlayani
    unserved = [
        nodes[i]["id_desa"]
        for i in range(num_nodes)
        if i not in served_nodes
    ]

    return {
        "status": "OPTIMAL" if routing.status() == 1 else "FEASIBLE",
        "total_distance": round(total_distance_all, 2),
        "total_vehicles_used": len(routes),
        "routes": routes,
        "unserved_nodes": unserved,
        "solver_wall_time_ms": wall_time_ms,
    }
