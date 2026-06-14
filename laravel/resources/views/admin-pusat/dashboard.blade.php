@extends('layouts.app')

@section('title', 'Central Admin Dashboard')
@section('page-title', 'National Overview')

@section('content')
<div class="stats-grid">
    <div class="stat-card glass-panel accent-info">
        <div class="stat-card-icon primary"><i class="bi bi-building"></i></div>
        <div class="stat-label">Active Distribution Centers</div>
        <div class="stat-value text-info">{{ $stats['total_pusat'] }}</div>
    </div>
    <div class="stat-card glass-panel accent-success">
        <div class="stat-card-icon success"><i class="bi bi-truck"></i></div>
        <div class="stat-label">Available Fleet</div>
        <div class="stat-value text-success">{{ $stats['armada_available'] }} <span class="text-muted" style="font-size: 0.9rem;">/ {{ $stats['total_armada'] }}</span></div>
    </div>
    <div class="stat-card glass-panel accent-warning">
        <div class="stat-card-icon warning"><i class="bi bi-box-seam"></i></div>
        <div class="stat-label">Relief Item SKUs</div>
        <div class="stat-value text-warning">{{ $stats['total_barang'] }}</div>
    </div>
    <div class="stat-card glass-panel accent-primary">
        <div class="stat-card-icon primary"><i class="bi bi-stack"></i></div>
        <div class="stat-label">Total Stock Items</div>
        <div class="stat-value">{{ number_format($stats['total_stok']) }}</div>
    </div>
</div>

<div class="glass-panel mt-xl">
    <div class="section-header">
        <h3><i class="bi bi-geo-alt"></i> Distribution Capacity Map</h3>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Distribution Center</th>
                    <th>Region</th>
                    <th>Status</th>
                    <th>Fleet Deployed</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pusatDistribusi as $pusat)
                <tr>
                    <td><strong>{{ $pusat->nama }}</strong></td>
                    <td>{{ $pusat->kabupaten->nama }}</td>
                    <td>
                        @if($pusat->status_aktif)
                            <span class="badge badge-success"><span class="badge-dot"></span> Active</span>
                        @else
                            <span class="badge badge-danger">Inactive</span>
                        @endif
                    </td>
                    <td>{{ $pusat->armada->count() }} units</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4">
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="bi bi-building"></i></div>
                            <div class="empty-state-text">No distribution centers registered yet.</div>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
