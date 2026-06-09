"""
Voronoi Clustering Topografis
Memetakan setiap desa ke pusat distribusi terdekat berdasarkan
jarak graf aktual (bukan Euclidean), dengan edge pruning untuk
rute yang terblokir.

Algoritma:
    Cluster(i) = argmin_{p ∈ P} { Dijkstra_Shortest_Path(i, p) }
    c_ij = ∞ (10^9) jika status_akses_terbuka = false
"""

import heapq
from typing import Any, Dict, List, Optional, Set, Tuple

INF = 10**9  # Representasi teknis untuk bobot tak hingga


def build_adjacency_graph(
    rute_data: List[Dict[str, Any]],
) -> Dict[int, List[Tuple[int, float]]]:
    """
    Membangun graf adjacency dari data tabel rute.
    Rute dengan status_akses_terbuka=False diberi bobot INF (edge pruning).

    Args:
        rute_data: List of dicts dengan keys:
            - id_titik_asal (int)
            - id_titik_tujuan (int)
            - jarak_km (float)
            - status_akses_terbuka (bool)

    Returns:
        Adjacency list: {node_id: [(neighbor_id, weight), ...]}
    """
    graph: Dict[int, List[Tuple[int, float]]] = {}

    for edge in rute_data:
        src = edge["id_titik_asal"]
        dst = edge["id_titik_tujuan"]
        distance = edge["jarak_km"]
        is_open = edge.get("status_akses_terbuka", True)

        # Edge Pruning: rute terblokir → bobot = INF
        weight = distance if is_open else INF

        if src not in graph:
            graph[src] = []
        if dst not in graph:
            graph[dst] = []

        # Graf bidireksional (undirected)
        graph[src].append((dst, weight))
        graph[dst].append((src, weight))

    return graph


def dijkstra(
    graph: Dict[int, List[Tuple[int, float]]],
    source: int,
) -> Dict[int, float]:
    """
    Implementasi Dijkstra single-source shortest path.

    Args:
        graph: Adjacency list
        source: Node ID sumber

    Returns:
        Dictionary {node_id: shortest_distance_from_source}
    """
    distances: Dict[int, float] = {source: 0.0}
    priority_queue: List[Tuple[float, int]] = [(0.0, source)]
    visited: Set[int] = set()

    while priority_queue:
        current_dist, current_node = heapq.heappop(priority_queue)

        if current_node in visited:
            continue
        visited.add(current_node)

        if current_node not in graph:
            continue

        for neighbor, weight in graph[current_node]:
            if neighbor in visited:
                continue
            new_dist = current_dist + weight
            if new_dist < distances.get(neighbor, float("inf")):
                distances[neighbor] = new_dist
                heapq.heappush(priority_queue, (new_dist, neighbor))

    return distances


def compute_voronoi_clusters(
    desa_ids: List[int],
    depot_ids: List[int],
    rute_data: List[Dict[str, Any]],
) -> Dict[int, Optional[int]]:
    """
    Menghitung Voronoi clustering topografis.
    Setiap desa dipetakan ke depot terdekat berdasarkan jarak graf.

    Args:
        desa_ids: List ID desa
        depot_ids: List ID pusat distribusi (depot)
        rute_data: Data rute dari tabel rute

    Returns:
        Dictionary {desa_id: depot_id} (None jika desa tidak terjangkau)
    """
    graph = build_adjacency_graph(rute_data)

    # Hitung shortest path dari setiap depot ke seluruh node
    depot_distances: Dict[int, Dict[int, float]] = {}
    for depot_id in depot_ids:
        depot_distances[depot_id] = dijkstra(graph, depot_id)

    # Assign setiap desa ke depot terdekat
    cluster_assignment: Dict[int, Optional[int]] = {}

    for desa_id in desa_ids:
        min_distance = float("inf")
        assigned_depot: Optional[int] = None

        for depot_id in depot_ids:
            dist = depot_distances[depot_id].get(desa_id, float("inf"))
            if dist < min_distance:
                min_distance = dist
                assigned_depot = depot_id

        # Jika jarak terkecil masih ≥ INF, desa tidak terjangkau
        if min_distance >= INF:
            cluster_assignment[desa_id] = None
        else:
            cluster_assignment[desa_id] = assigned_depot

    return cluster_assignment


def compute_distance_matrix(
    node_ids: List[int],
    rute_data: List[Dict[str, Any]],
) -> List[List[float]]:
    """
    Menghitung matriks jarak all-pairs shortest path untuk subset node.

    Args:
        node_ids: List ID node (depots + desa dalam satu cluster)
        rute_data: Data rute dari tabel rute

    Returns:
        Matriks jarak NxN (list of lists)
    """
    graph = build_adjacency_graph(rute_data)

    n = len(node_ids)
    id_to_idx = {node_id: idx for idx, node_id in enumerate(node_ids)}

    matrix: List[List[float]] = [[INF] * n for _ in range(n)]

    for i in range(n):
        matrix[i][i] = 0.0

    for i, source_id in enumerate(node_ids):
        distances = dijkstra(graph, source_id)
        for j, target_id in enumerate(node_ids):
            if target_id in distances:
                matrix[i][j] = distances[target_id]

    return matrix
