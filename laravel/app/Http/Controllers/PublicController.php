<?php

namespace App\Http\Controllers;

use App\Models\Desa;
use App\Models\PusatDistribusi;
use App\Models\Rute;
use App\Models\ManifestPengiriman;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    /**
     * Halaman peta publik — hak akses tanpa otentikasi.
     * Hanya operasi idempoten GET pada endpoint peta.
     */
    public function index()
    {
        return view('public.map');
    }

    /**
     * API endpoint: GeoJSON data untuk peta publik.
     * Payload diminifikasi — hanya id, coordinates, urgency_score.
     */
    public function mapData(): JsonResponse
    {
        // Data desa (demand nodes)
        $desaFeatures = Desa::select('id', 'nama', 'lat', 'long_decimal', 'korban_selamat', 'status_aman')
            ->with(['demands' => function ($query) {
                $query->select('id_desa')
                    ->selectRaw('MAX(urgency_score) as max_urgency')
                    ->groupBy('id_desa');
            }])
            ->get()
            ->map(function ($desa) {
                $urgency = $desa->demands->first()?->max_urgency ?? 0;
                return [
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'Point',
                        'coordinates' => [(float) $desa->long_decimal, (float) $desa->lat],
                    ],
                    'properties' => [
                        'id' => $desa->id,
                        'nama' => $desa->nama,
                        'urgency_score' => round((float) $urgency, 2),
                        'status_aman' => $desa->status_aman,
                        'korban_selamat' => $desa->korban_selamat,
                        'type' => 'desa',
                    ],
                ];
            });

        // Data pusat distribusi (depot nodes)
        $depotFeatures = PusatDistribusi::aktif()
            ->select('id', 'nama', 'lat', 'long_decimal')
            ->get()
            ->map(function ($pusat) {
                return [
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'Point',
                        'coordinates' => [(float) $pusat->long_decimal, (float) $pusat->lat],
                    ],
                    'properties' => [
                        'id' => $pusat->id,
                        'nama' => $pusat->nama,
                        'type' => 'depot',
                    ],
                ];
            });

        // Data Rute (Edges)
        $rutes = Rute::all()->map(function ($rute) {
            $asal = Desa::find($rute->id_titik_asal) ?? PusatDistribusi::find($rute->id_titik_asal);
            $tujuan = Desa::find($rute->id_titik_tujuan) ?? PusatDistribusi::find($rute->id_titik_tujuan);
            
            if (!$asal || !$tujuan) return null;

            return [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'LineString',
                    'coordinates' => [
                        [(float) $asal->long_decimal, (float) $asal->lat],
                        [(float) $tujuan->long_decimal, (float) $tujuan->lat]
                    ]
                ],
                'properties' => [
                    'id' => $rute->id,
                    'status_akses_terbuka' => $rute->status_akses_terbuka,
                    'jarak_km' => $rute->jarak_km,
                    'type' => 'rute'
                ]
            ];
        })->filter()->values();

        // Data Manifest Aktif (Clusters)
        $activeManifests = ManifestPengiriman::where('status', 'In-Transit')
            ->whereNotNull('route_json')
            ->get()
            ->map(function ($manifest) {
                return [
                    'id_manifest' => $manifest->id,
                    'id_armada' => $manifest->id_armada,
                    'id_pusat' => $manifest->id_pusat,
                    'route_json' => is_string($manifest->route_json) ? json_decode($manifest->route_json, true) : $manifest->route_json
                ];
            });

        $geojson = [
            'type' => 'FeatureCollection',
            'features' => $desaFeatures->merge($depotFeatures)->merge($rutes)->values()->toArray(),
            'active_routes' => $activeManifests
        ];

        return response()->json($geojson);
    }
}
