@extends('layouts.app')

@section('title', 'Regional Admin Dashboard')
@section('page-title', 'Regional Overview')

@section('content')
<div class="stats-grid">
    <div class="stat-card glass-panel accent-info">
        <div class="stat-card-icon primary"><i class="bi bi-file-earmark-text"></i></div>
        <div class="stat-label">Draft Requests</div>
        <div class="stat-value text-muted">{{ $draftCount }}</div>
    </div>
    <div class="stat-card glass-panel accent-warning">
        <div class="stat-card-icon warning"><i class="bi bi-hourglass-split"></i></div>
        <div class="stat-label">Queued for Optimization</div>
        <div class="stat-value text-warning">{{ $queuedCount }}</div>
    </div>
    <div class="stat-card glass-panel accent-primary">
        <div class="stat-card-icon primary"><i class="bi bi-clipboard-data"></i></div>
        <div class="stat-label">Total Historical Requests</div>
        <div class="stat-value text-info">{{ $totalDemand }}</div>
    </div>
</div>

<div class="glass-panel mt-xl">
    <div class="section-header">
        <h3><i class="bi bi-geo-alt-fill"></i> Affected Villages</h3>
        <span class="section-count">{{ count($desa) }} villages</span>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Village</th>
                    <th>Safety Status</th>
                    <th>Population</th>
                    <th>Survivors</th>
                    <th>Demands Logged</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($desa as $d)
                <tr>
                    <td><strong>{{ $d->nama }}</strong></td>
                    <td>
                        @if($d->status_aman)
                            <span class="badge badge-success"><span class="badge-dot"></span> Safe</span>
                        @else
                            <span class="badge badge-danger"><span class="badge-dot"></span> Affected</span>
                        @endif
                    </td>
                    <td class="text-mono">{{ number_format($d->populasi) }}</td>
                    <td class="text-mono">{{ number_format($d->korban_selamat) }}</td>
                    <td>{{ $d->demands_count }} entries</td>
                    <td>
                        <a href="{{ route('admin-daerah.demand.index') }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-circle"></i> Submit Demand
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="bi bi-geo-alt"></i></div>
                            <div class="empty-state-text">No village data available for your region.</div>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
