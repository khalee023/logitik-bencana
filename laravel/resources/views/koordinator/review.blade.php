@extends('layouts.app')

@section('title', 'Simulation Review | Coordinator')
@section('page-title', 'Route Simulation Review (Human-in-the-Loop)')

@section('content')
<div class="glass-panel" style="padding: 1.5rem; margin-bottom: 2rem;">
    <h3 style="margin-top:0; color: var(--color-warning);">Status: Awaiting Coordinator Approval</h3>
    <p class="text-muted">
        OR-Tools heuristic engine has yielded an optimal routing solution. Total vehicles assigned: <strong>{{ count($result['routes']) }}</strong>.
        Total distance: <strong>{{ number_format($result['total_distance'], 2) }} km</strong>.
    </p>

    <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
        <form action="{{ route('koordinator.approve') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-success"><i class="bi bi-check-circle"></i> Approve & Publish Manifest (State-Commit)</button>
        </form>

        <form action="{{ route('koordinator.reject') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-danger"><i class="bi bi-x-circle"></i> Reject & Discard</button>
        </form>
    </div>
</div>

<h3 style="margin-bottom: 1rem;">Fleet Assignment Routes</h3>
<div class="stats-grid">
    @foreach($result['routes'] as $route)
    <div class="glass-panel" style="padding: 1rem;">
        <h4 style="margin-top:0; border-bottom: 1px solid var(--color-glass-border); padding-bottom: 0.5rem;">
            Fleet ID: #{{ $route['id_armada'] }}
        </h4>
        <div style="font-size: 0.85rem; margin-bottom: 1rem; color: var(--color-text-muted);">
            <div>Travel Distance: <strong>{{ number_format($route['total_distance'], 2) }} km</strong></div>
            <div>Weight Loaded: <strong>{{ number_format($route['utilization_berat_pct'], 2) }} %</strong></div>
            <div>Volume Loaded: <strong>{{ number_format($route['utilization_vol_pct'], 2) }} %</strong></div>
        </div>
        
        <h5 style="margin: 0 0 0.5rem 0;">Visit Sequence:</h5>
        <ol style="padding-left: 1.5rem; font-size: 0.85rem; margin: 0;">
            @foreach($route['stops'] as $stop)
                @if(is_null($stop['id_desa']))
                    <li style="color: var(--color-info);">Distribution Center (Depot)</li>
                @else
                    <li>
                        Village ID: {{ $stop['id_desa'] }}
                        @if(isset($stop['load_berat']) && $stop['load_berat'] > 0)
                            <span class="text-success">(Cumulative Load: {{ $stop['load_berat'] }} kg)</span>
                        @endif
                    </li>
                @endif
            @endforeach
        </ol>
    </div>
    @endforeach
</div>
@endsection
