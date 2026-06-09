@extends('layouts.app')

@section('title', 'Admin Pusat Dashboard')
@section('page-title', 'Overview Nasional')

@section('content')
<div class="stats-grid">
    <div class="stat-card glass-panel">
        <div class="stat-label">Pusat Distribusi Aktif</div>
        <div class="stat-value text-info">{{ $stats['total_pusat'] }}</div>
    </div>
    <div class="stat-card glass-panel">
        <div class="stat-label">Total Armada Tersedia</div>
        <div class="stat-value text-success">{{ $stats['armada_available'] }} / {{ $stats['total_armada'] }}</div>
    </div>
    <div class="stat-card glass-panel">
        <div class="stat-label">SKU Master Bantuan</div>
        <div class="stat-value text-warning">{{ $stats['total_barang'] }}</div>
    </div>
    <div class="stat-card glass-panel">
        <div class="stat-label">Total Item Stok</div>
        <div class="stat-value">{{ number_format($stats['total_stok']) }}</div>
    </div>
</div>

<div class="glass-panel" style="margin-top: 2rem; padding: 1.5rem;">
    <h3 style="margin-top:0; border-bottom: 1px solid var(--color-glass-border); padding-bottom: 0.5rem;">Peta Kapasitas Distribusi</h3>
    
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Pusat Distribusi</th>
                    <th>Wilayah</th>
                    <th>Status</th>
                    <th>Armada Ditempatkan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pusatDistribusi as $pusat)
                <tr>
                    <td>{{ $pusat->nama }}</td>
                    <td>{{ $pusat->kabupaten->nama }}</td>
                    <td>
                        @if($pusat->status_aktif)
                            <span class="badge badge-success">Aktif</span>
                        @else
                            <span class="badge badge-danger">Inaktif</span>
                        @endif
                    </td>
                    <td>{{ $pusat->armada->count() }} unit</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="text-align: center;" class="text-muted">Data kosong.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
