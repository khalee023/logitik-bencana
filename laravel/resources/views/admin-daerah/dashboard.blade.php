@extends('layouts.app')

@section('title', 'Admin Daerah Dashboard')
@section('page-title', 'Overview Daerah')

@section('content')
<div class="stats-grid">
    <div class="stat-card glass-panel">
        <div class="stat-label">Total Permintaan Draft</div>
        <div class="stat-value text-muted">{{ $draftCount }}</div>
    </div>
    <div class="stat-card glass-panel">
        <div class="stat-label">Permintaan Dalam Antrean (Queued)</div>
        <div class="stat-value text-warning">{{ $queuedCount }}</div>
    </div>
    <div class="stat-card glass-panel">
        <div class="stat-label">Total Historis Permintaan</div>
        <div class="stat-value text-info">{{ $totalDemand }}</div>
    </div>
</div>

<div class="glass-panel" style="margin-top: 2rem; padding: 1.5rem;">
    <h3 style="margin-top:0; border-bottom: 1px solid var(--color-glass-border); padding-bottom: 0.5rem;">Daftar Desa Terdampak</h3>
    
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Desa</th>
                    <th>Status Aman</th>
                    <th>Populasi</th>
                    <th>Korban Selamat</th>
                    <th>Demand Tercatat</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($desa as $d)
                <tr>
                    <td>{{ $d->nama }}</td>
                    <td>
                        @if($d->status_aman)
                            <span class="badge badge-success">Aman</span>
                        @else
                            <span class="badge badge-danger">Terdampak</span>
                        @endif
                    </td>
                    <td>{{ number_format($d->populasi) }}</td>
                    <td>{{ number_format($d->korban_selamat) }}</td>
                    <td>{{ $d->demands_count }} logs</td>
                    <td>
                        <a href="{{ route('admin-daerah.demand.index') }}" class="btn btn-primary" style="padding: 0.2rem 0.5rem; font-size: 0.8rem;">
                            Input Kebutuhan
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align: center;" class="text-muted">Data kosong.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
