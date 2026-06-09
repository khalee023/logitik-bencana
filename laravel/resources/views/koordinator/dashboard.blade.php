@extends('layouts.app')

@section('title', 'Coordinator Dashboard')
@section('page-title', 'Global Command Center')

@section('content')
<div class="stats-grid">
    <div class="stat-card glass-panel" style="border-left: 4px solid var(--color-warning);">
        <div class="stat-label">Demands Queued</div>
        <div class="stat-value text-warning">{{ $stats['demand_queued'] }}</div>
    </div>
    <div class="stat-card glass-panel" style="border-left: 4px solid var(--color-primary);">
        <div class="stat-label">Manifests In-Transit</div>
        <div class="stat-value text-primary">{{ $stats['manifest_transit'] }}</div>
    </div>
    <div class="stat-card glass-panel" style="border-left: 4px solid var(--color-success);">
        <div class="stat-label">Demands Fulfilled</div>
        <div class="stat-value text-success">{{ $stats['demand_fulfilled'] }}</div>
    </div>
</div>

<div class="glass-panel" style="margin-top: 2rem; padding: 2rem; text-align: center;">
    <h2 style="color: var(--color-primary-light); margin-bottom: 1rem;"><i class="bi bi-cpu"></i> Heuristic Optimization Engine (OR-Tools)</h2>
    <p style="color: var(--color-text-muted); max-width: 600px; margin: 0 auto 2rem auto;">
        Run the CVRPTW (Capacitated Vehicle Routing Problem with Time Windows) algorithm via FastAPI microservice to solve logistics routing for the highest urgency demands.
    </p>
    
    <form action="{{ route('koordinator.optimize') }}" method="POST" id="optimize-form">
        @csrf
        <button type="submit" class="btn btn-primary" style="padding: 1rem 2rem; font-size: 1.2rem; display: inline-flex; align-items: center; gap: 0.5rem;" onclick="this.disabled=true; this.innerHTML='<i class=\'bi bi-hourglass-split\'></i> Processing Computation...'; document.getElementById('optimize-form').submit();">
            <i class="bi bi-play-fill"></i> Run Global Optimization Simulation
        </button>
    </form>
</div>
@endsection
