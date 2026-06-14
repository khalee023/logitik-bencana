@extends('layouts.app')

@section('title', 'Simulation Review | Coordinator')
@section('page-title', 'Route Simulation Review (Human-in-the-Loop)')

@section('content')
{{-- Action Bar --}}
<div class="action-bar mb-xl">
    <div class="action-bar-status">
        <h3 style="margin: 0; color: var(--color-urgency-medium);"><i class="bi bi-exclamation-triangle"></i> Awaiting Coordinator Approval</h3>
        <p class="text-muted" style="margin: var(--space-xs) 0 0 0; font-size: 0.85rem;">
            OR-Tools heuristic engine yielded an optimal routing solution.
            <strong>{{ count($result['routes']) }}</strong> vehicle(s) assigned —
            Total distance: <strong>{{ number_format($result['total_distance'], 2) }} km</strong>.
        </p>
    </div>
    <div class="action-bar-buttons">
        <form action="{{ route('koordinator.approve') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-success btn-lg"><i class="bi bi-check-circle"></i> Approve & Publish</button>
        </form>
        <form action="{{ route('koordinator.reject') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-danger btn-lg"><i class="bi bi-x-circle"></i> Reject</button>
        </form>
    </div>
</div>

{{-- Summary Metrics --}}
<div class="summary-row">
    <div class="summary-item">
        <div class="summary-item-value text-accent">{{ count($result['routes']) }}</div>
        <div class="summary-item-label">Vehicles Assigned</div>
    </div>
    <div class="summary-item">
        <div class="summary-item-value text-warning">{{ number_format($result['total_distance'], 1) }} km</div>
        <div class="summary-item-label">Total Distance</div>
    </div>
    @php
        $avgWeight = collect($result['routes'])->avg('utilization_berat_pct') ?? 0;
        $avgVol = collect($result['routes'])->avg('utilization_vol_pct') ?? 0;
    @endphp
    <div class="summary-item">
        <div class="summary-item-value" style="color: {{ $avgWeight > 85 ? 'var(--color-urgency-critical)' : 'var(--color-urgency-low)' }};">{{ number_format($avgWeight, 1) }}%</div>
        <div class="summary-item-label">Avg Weight Load</div>
    </div>
    <div class="summary-item">
        <div class="summary-item-value" style="color: {{ $avgVol > 85 ? 'var(--color-urgency-critical)' : 'var(--color-urgency-low)' }};">{{ number_format($avgVol, 1) }}%</div>
        <div class="summary-item-label">Avg Volume Load</div>
    </div>
</div>

{{-- Route Map --}}
@if(isset($mapData))
<div class="glass-panel mb-xl">
    <div class="section-header">
        <h3><i class="bi bi-map"></i> Optimized Route Map</h3>
    </div>
    <div class="review-map" id="review-map"></div>
</div>
@endif

{{-- Route Cards --}}
<div class="section-header">
    <h3><i class="bi bi-signpost-2"></i> Fleet Assignment Routes</h3>
</div>

<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));">
    @foreach($result['routes'] as $route)
    <div class="route-card">
        <div class="route-card-header">
            <h4 style="margin: 0;"><i class="bi bi-truck"></i> Fleet #{{ $route['id_armada'] }}</h4>
            <span class="badge badge-primary">{{ count($route['stops']) }} stops</span>
        </div>

        <div class="route-card-metrics">
            <div class="route-metric">
                <div class="route-metric-value text-accent">{{ number_format($route['total_distance'], 1) }}</div>
                <div class="route-metric-label">Distance (km)</div>
            </div>
            <div class="route-metric">
                <div class="route-metric-value">{{ number_format($route['utilization_berat_pct'], 1) }}%</div>
                <div class="route-metric-label">Weight Load</div>
                <div class="utilization-bar">
                    <div class="utilization-bar-fill {{ $route['utilization_berat_pct'] > 85 ? 'high' : '' }}" style="width: {{ min($route['utilization_berat_pct'], 100) }}%;"></div>
                </div>
            </div>
            <div class="route-metric">
                <div class="route-metric-value">{{ number_format($route['utilization_vol_pct'], 1) }}%</div>
                <div class="route-metric-label">Volume Load</div>
                <div class="utilization-bar">
                    <div class="utilization-bar-fill {{ $route['utilization_vol_pct'] > 85 ? 'high' : '' }}" style="width: {{ min($route['utilization_vol_pct'], 100) }}%;"></div>
                </div>
            </div>
        </div>

        <h5 style="margin: 0 0 var(--space-sm) 0; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--color-text-muted);">Visit Sequence</h5>
        <ul class="visit-sequence">
            @foreach($route['stops'] as $stop)
                <li class="visit-stop">
                    @if(is_null($stop['id_desa']))
                        <span class="visit-stop-dot depot"></span>
                        <div class="visit-stop-content">
                            <div class="visit-stop-name" style="color: var(--color-accent-primary);">Distribution Center (Depot)</div>
                            <div class="visit-stop-meta">Starting & return point</div>
                        </div>
                    @else
                        <span class="visit-stop-dot"></span>
                        <div class="visit-stop-content">
                            <div class="visit-stop-name">
                                {{ $desaNames[$stop['id_desa']] ?? ('Village #' . $stop['id_desa']) }}
                            </div>
                            @if(isset($stop['load_berat']) && $stop['load_berat'] > 0)
                                <div class="visit-stop-meta">Cumulative: {{ $stop['load_berat'] }} kg</div>
                            @endif
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
    @endforeach
</div>
@endsection

@push('scripts')
@if(isset($mapData))
<script>
document.addEventListener('DOMContentLoaded', function() {
    const mapData = @json($mapData);
    const routes = @json($result['routes']);
    const clusterColors = ['#ffc107', '#0d6efd', '#20c997', '#e83e8c', '#6f42c1', '#fd7e14', '#00d4ff', '#ff3366'];

    const map = L.map('review-map', { zoomControl: true }).setView([-6.8, 107.0], 10);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap',
        maxZoom: 18,
    }).addTo(map);

    const bounds = [];

    // Plot depots
    if (mapData.depots) {
        Object.values(mapData.depots).forEach(depot => {
            L.circleMarker([depot.lat, depot.lng], {
                radius: 10, fillColor: '#00d4ff', color: '#fff', weight: 2, fillOpacity: 0.9
            }).addTo(map).bindPopup(`<b>${depot.nama}</b><br><small>Distribution Center</small>`);
            bounds.push([depot.lat, depot.lng]);
        });
    }

    // Plot desa nodes
    if (mapData.desa) {
        mapData.desa.forEach(d => {
            L.circleMarker([d.lat, d.lng], {
                radius: 7, fillColor: '#dc3545', color: '#fff', weight: 1, fillOpacity: 0.8
            }).addTo(map).bindPopup(`<b>${d.nama}</b>`);
            bounds.push([d.lat, d.lng]);
        });
    }

    // Draw routes
    routes.forEach((route, idx) => {
        const color = clusterColors[idx % clusterColors.length];
        const latlngs = [];

        route.stops.forEach(stop => {
            if (stop.id_desa === null && mapData.depots && mapData.armadas) {
                const depotId = mapData.armadas[route.id_armada];
                if (depotId && mapData.depots[depotId]) {
                    const depot = mapData.depots[depotId];
                    latlngs.push([depot.lat, depot.lng]);
                }
            } else if (mapData.desa) {
                const d = mapData.desa.find(v => v.id === stop.id_desa);
                if (d) latlngs.push([d.lat, d.lng]);
            }
        });

        if (latlngs.length > 1) {
            L.polyline(latlngs, { color, weight: 4, opacity: 0.85, lineCap: 'round' })
                .addTo(map)
                .bindPopup(`<b>Fleet #${route.id_armada}</b><br>Distance: ${route.total_distance.toFixed(1)} km`);
        }
    });

    if (bounds.length > 0) {
        map.fitBounds(bounds, { padding: [40, 40] });
    }
});
</script>
@endif
@endpush
