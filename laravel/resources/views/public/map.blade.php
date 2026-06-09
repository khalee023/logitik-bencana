@extends('layouts.app')

@section('title', 'Peta Bencana Publik')

@push('styles')
<style>
    body, html { margin: 0; padding: 0; height: 100%; overflow: hidden; }
    .public-map-container {
        position: relative;
        width: 100vw;
        height: 100vh;
    }
    #map {
        width: 100%;
        height: 100%;
        z-index: 1;
        background: var(--color-bg); /* Dark mode map fallback */
    }
    .map-overlay {
        position: absolute;
        top: 20px;
        left: 20px;
        z-index: 1000;
        pointer-events: none;
    }
    .map-overlay-content {
        pointer-events: auto;
    }
    .map-legend {
        position: absolute;
        bottom: 30px;
        right: 30px;
        z-index: 1000;
        pointer-events: auto;
    }
    /* Leaflet Dark mode tweaks */
    .leaflet-layer,
    .leaflet-control-zoom-in,
    .leaflet-control-zoom-out,
    .leaflet-control-attribution {
        filter: invert(100%) hue-rotate(180deg) brightness(95%) contrast(90%);
    }
    .pulse-marker {
        border-radius: 50%;
        box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
        70% { box-shadow: 0 0 0 15px rgba(220, 53, 69, 0); }
        100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
    }
</style>
@endpush

@section('content')
<div class="public-map-container">
    <div id="map"></div>
    
    <div class="map-overlay">
        <div class="glass-panel map-overlay-content" style="padding: 1.5rem; max-width: 350px;">
            <h3 style="margin-top:0; color: var(--color-primary-light);"><i class="bi bi-shield-shaded"></i> DLCC Live Map</h3>
            <p style="font-size: 0.9rem; color: var(--color-text-muted); margin-bottom: 1rem;">
                Real-time monitoring of disaster status and regional logistics distribution.
            </p>
            <div style="display: flex; gap: 0.5rem;">
                <a href="{{ route('login') }}" class="btn btn-primary" style="flex: 1; text-align: center;">Official Login</a>
            </div>
        </div>
    </div>

    <div class="map-legend glass-panel" style="padding: 1rem;">
        <h4 style="margin-top:0; margin-bottom: 0.5rem; font-size: 0.9rem;">Legend</h4>
        <div style="display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.8rem;">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <span style="display: inline-block; width: 12px; height: 12px; background: #0dcaf0; border-radius: 50%;"></span> Distribution Center
            </div>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <span style="display: inline-block; width: 12px; height: 12px; background: #198754; border-radius: 50%;"></span> Safe Village
            </div>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <span class="pulse-marker" style="display: inline-block; width: 12px; height: 12px; background: #dc3545; border-radius: 50%;"></span> Critical Village
            </div>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <span style="display: inline-block; width: 20px; height: 3px; background: #6c757d;"></span> Available Route
            </div>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <span style="display: inline-block; width: 20px; height: 3px; background: #dc3545; border-bottom: 2px dashed #dc3545;"></span> Blocked Route
            </div>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <span style="display: inline-block; width: 20px; height: 4px; background: #ffc107;"></span> Active Logistics Cluster
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Init Leaflet map centered to Indonesia
    const map = L.map('map', {
        zoomControl: false
    }).setView([-2.5, 118.0], 5);

    L.control.zoom({ position: 'bottomleft' }).addTo(map);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 18,
    }).addTo(map);

    const markersLayer = L.layerGroup().addTo(map);
    const edgesLayer = L.layerGroup().addTo(map);
    const activeRoutesLayer = L.layerGroup().addTo(map);

    // Color palette for clusters
    const clusterColors = ['#ffc107', '#0d6efd', '#20c997', '#e83e8c', '#6f42c1', '#fd7e14'];

    async function loadMapData() {
        try {
            const data = await fetchAPI('{{ route("api.map-data") }}');
            markersLayer.clearLayers();
            edgesLayer.clearLayers();
            activeRoutesLayer.clearLayers();

            const nodeCoordinates = {};

            // Plot base graph (Nodes & Edges)
            L.geoJSON(data.features, {
                pointToLayer: function (feature, latlng) {
                    // Save coordinates for active routes lookup
                    if (feature.properties.type === 'depot') {
                        nodeCoordinates['depot_' + feature.properties.id] = latlng;
                    } else if (feature.properties.type === 'desa') {
                        nodeCoordinates['desa_' + feature.properties.id] = latlng;
                    }

                    let color = '#198754'; // Aman
                    let radius = 6;
                    let className = '';

                    if (feature.properties.type === 'depot') {
                        color = '#0dcaf0'; // Info / Depot
                        radius = 8;
                    } else if (feature.properties.type === 'desa') {
                        if (!feature.properties.status_aman || feature.properties.urgency_score > 0) {
                            color = '#dc3545'; // Danger / Terdampak
                            className = feature.properties.urgency_score > 50 ? 'pulse-marker' : '';
                        }
                    }

                    const markerOptions = {
                        radius: radius,
                        fillColor: color,
                        color: '#fff',
                        weight: 1,
                        opacity: 1,
                        fillOpacity: 0.8,
                        className: className
                    };

                    return L.circleMarker(latlng, markerOptions);
                },
                style: function(feature) {
                    if (feature.geometry.type === 'LineString') {
                        if (feature.properties.status_akses_terbuka) {
                            return { color: '#6c757d', weight: 2, opacity: 0.5 };
                        } else {
                            return { color: '#dc3545', weight: 2, opacity: 0.8, dashArray: '5, 5' };
                        }
                    }
                },
                onEachFeature: function (feature, layer) {
                    if (feature.geometry.type === 'Point') {
                        if (feature.properties.type === 'depot') {
                            layer.bindPopup(`<b>${feature.properties.nama}</b><br>Distribution Center`);
                        } else {
                            const status = feature.properties.status_aman ? 'Safe' : 'Impacted';
                            const score = feature.properties.urgency_score;
                            layer.bindPopup(`
                                <b>${feature.properties.nama}</b><br>
                                Status: ${status}<br>
                                Survivors: ${feature.properties.korban_selamat}<br>
                                Urgency Score: ${score.toFixed(2)}
                            `);
                        }
                    } else if (feature.geometry.type === 'LineString') {
                        layer.addTo(edgesLayer);
                    }
                }
            }).addTo(markersLayer);

            // Draw active manifest routes
            if (data.active_routes && data.active_routes.length > 0) {
                data.active_routes.forEach((manifest, index) => {
                    const color = clusterColors[index % clusterColors.length];
                    const latlngs = [];
                    
                    if (manifest.route_json) {
                        manifest.route_json.forEach(stop => {
                            let coordKey = stop.id_desa === null ? ('depot_' + manifest.id_pusat) : ('desa_' + stop.id_desa);
                            if (nodeCoordinates[coordKey]) {
                                latlngs.push(nodeCoordinates[coordKey]);
                            }
                        });

                        if (latlngs.length > 1) {
                            let popupContent = `<b>Fleet #${manifest.id_armada}</b><br>Manifest ID: ${manifest.id_manifest}`;
                            
                            let deliveryLogs = '';
                            manifest.route_json.forEach(stop => {
                                if (stop.id_desa !== null && stop.delivered_items && stop.delivered_items.length > 0) {
                                    deliveryLogs += `<b>To Village ${stop.id_desa}:</b><ul style="margin: 0; padding-left: 15px; font-size: 0.85em;">`;
                                    stop.delivered_items.forEach(item => {
                                        deliveryLogs += `<li>${item.kuantitas}x ${item.nama_barang}</li>`;
                                    });
                                    deliveryLogs += `</ul>`;
                                }
                            });

                            if (deliveryLogs) {
                                popupContent += `<hr style="margin: 5px 0;"><b>Payload Logs:</b><br>` + deliveryLogs;
                            }

                            const polyline = L.polyline(latlngs, {
                                color: color,
                                weight: 4,
                                opacity: 0.9,
                                lineCap: 'round',
                                lineJoin: 'round'
                            });
                            polyline.bindPopup(popupContent);
                            polyline.addTo(activeRoutesLayer);
                            
                            // Add animation effect
                            if (typeof L.polylineDecorator === 'function') {
                                L.polylineDecorator(polyline, {
                                    patterns: [
                                        {offset: 25, repeat: 50, symbol: L.Symbol.arrowHead({pixelSize: 10, pathOptions: {color: color, fillOpacity: 1, weight: 0}})}
                                    ]
                                }).addTo(activeRoutesLayer);
                            }
                        }
                    }
                });
            }

            // Fit bounds to features if any exist
            if (data.features && data.features.length > 0) {
                const allLayers = [
                    ...markersLayer.getLayers(),
                    ...edgesLayer.getLayers(),
                    ...activeRoutesLayer.getLayers()
                ];
                if (allLayers.length > 0) {
                    const group = new L.featureGroup(allLayers);
                    map.fitBounds(group.getBounds(), { padding: [50, 50] });
                }
            }
        } catch (error) {
            console.error('Failed to load map data:', error);
            showToast('Failed to load map data', 'error');
        }
    }

    loadMapData();
    // Refresh every 30s
    setInterval(loadMapData, 30000);
});
</script>
@endpush
