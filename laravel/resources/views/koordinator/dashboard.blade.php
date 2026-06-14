@extends('layouts.app')

@section('title', 'Coordinator Dashboard')
@section('page-title', 'Global Command Center')

@section('content')
<div class="stats-grid">
    <div class="stat-card glass-panel accent-warning">
        <div class="stat-card-icon warning"><i class="bi bi-hourglass-split"></i></div>
        <div class="stat-label">Demands Queued</div>
        <div class="stat-value text-warning">{{ $stats['demand_queued'] }}</div>
    </div>
    <div class="stat-card glass-panel accent-primary">
        <div class="stat-card-icon primary"><i class="bi bi-truck"></i></div>
        <div class="stat-label">Manifests In-Transit</div>
        <div class="stat-value text-primary">{{ $stats['manifest_transit'] }}</div>
    </div>
    <div class="stat-card glass-panel accent-success">
        <div class="stat-card-icon success"><i class="bi bi-check-circle"></i></div>
        <div class="stat-label">Demands Fulfilled</div>
        <div class="stat-value text-success">{{ $stats['demand_fulfilled'] }}</div>
    </div>
    <div class="stat-card glass-panel accent-info">
        <div class="stat-card-icon primary"><i class="bi bi-file-earmark-text"></i></div>
        <div class="stat-label">Total Manifests</div>
        <div class="stat-value text-info">{{ $stats['total_manifest'] }}</div>
    </div>
</div>

<div class="glass-panel optimization-hero">
    <h2><i class="bi bi-cpu"></i> Heuristic Optimization Engine (OR-Tools)</h2>
    <p class="text-muted">
        Run the CVRPTW (Capacitated Vehicle Routing Problem with Time Windows) algorithm via FastAPI microservice
        to solve logistics routing for the highest urgency demands.
    </p>

    <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap; margin-bottom: 1rem;">
        @if($stats['demand_queued'] > 0)
            <form action="{{ route('koordinator.optimize') }}" method="POST" id="optimize-form">
                @csrf
                <button type="submit" class="btn btn-primary btn-xl btn-pulse" onclick="this.disabled=true; this.innerHTML='<span class=\'spinner\'></span> Processing Computation...'; document.getElementById('optimize-form').submit();">
                    <i class="bi bi-play-fill"></i> Run Global Optimization Simulation
                </button>
            </form>
        @else
            <button class="btn btn-outline btn-xl" disabled>
                <i class="bi bi-play-fill"></i> No Queued Demands Available
            </button>
        @endif

        <form action="{{ route('koordinator.update-urgency') }}" method="POST" id="urgency-form">
            @csrf
            <button type="submit" class="btn btn-secondary btn-xl" onclick="this.disabled=true; this.innerHTML='<span class=\'spinner\'></span> Updating...'; document.getElementById('urgency-form').submit();">
                <i class="bi bi-arrow-clockwise"></i> Sync Urgency Scores
            </button>
        </form>
    </div>

    @if($stats['demand_queued'] > 0)
        <p class="text-muted mt-md" style="font-size: 0.8rem;">
            {{ $stats['demand_queued'] }} demand(s) queued — ready for optimization
        </p>
    @else
        <p class="text-muted mt-md" style="font-size: 0.8rem;">
            Regional admins must queue demand requests before optimization can be triggered.
        </p>
    @endif
</div>

@if(isset($queuedDemands) && $queuedDemands->count() > 0)
<div class="glass-panel mt-xl">
    <div class="section-header">
        <h3><i class="bi bi-lightning-charge"></i> Queued Demands (Top Urgency)</h3>
        <span class="section-count">{{ $queuedDemands->count() }} pending</span>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Village</th><th>Item</th><th>Qty</th><th>Urgency</th>
                </tr>
            </thead>
            <tbody>
                @foreach($queuedDemands as $d)
                <tr>
                    <td><strong>{{ $d->desa->nama ?? 'Unknown' }}</strong></td>
                    <td>{{ $d->barang->nama ?? 'Unknown' }}</td>
                    <td class="text-mono">{{ number_format($d->kuantitas) }}</td>
                    <td>
                        @php
                            $score = $d->urgency_score ?? 0;
                            $level = $score >= 7.5 ? 'critical' : ($score >= 5 ? 'high' : ($score >= 2.5 ? 'medium' : 'low'));
                        @endphp
                        <span class="urgency-indicator">
                            <span class="urgency-dot {{ $level }}"></span>
                            {{ number_format($score, 1) }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
