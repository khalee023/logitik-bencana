@extends('layouts.app')
@section('title', 'Manifest History')
@section('page-title', 'Shipping Manifest History')
@section('content')

<div class="glass-panel">
    <div class="section-header">
        <h3><i class="bi bi-file-earmark-text"></i> All Manifests</h3>
        <span class="section-count">{{ $manifests instanceof \Illuminate\Pagination\LengthAwarePaginator ? $manifests->total() : count($manifests) }} records</span>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Manifest Code</th>
                    <th>Distribution Center</th>
                    <th>Fleet Plate</th>
                    <th>Departed At</th>
                    <th>Arrived At</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($manifests as $m)
                <tr>
                    <td><code class="text-mono">{{ $m->kode_manifest }}</code></td>
                    <td><strong>{{ $m->pusatDistribusi->nama }}</strong></td>
                    <td><code>{{ $m->armada->plat_nomor }}</code></td>
                    <td class="text-muted" style="font-size: 0.8rem;">{{ $m->waktu_berangkat }}</td>
                    <td class="text-muted" style="font-size: 0.8rem;">{{ $m->waktu_tiba ?? '—' }}</td>
                    <td>
                        @if($m->status === 'In-Transit')
                            <span class="badge badge-transit"><span class="badge-dot"></span> In-Transit</span>
                        @elseif($m->status === 'Delivered')
                            <span class="badge badge-delivered"><span class="badge-dot"></span> Delivered</span>
                        @else
                            <span class="badge badge-info">{{ $m->status }}</span>
                        @endif
                    </td>
                    <td>
                        @if($m->status === 'In-Transit')
                            <form action="{{ route('koordinator.manifest.complete', $m) }}" method="POST" style="display: inline;"
                                  onsubmit="return confirm('Mark this manifest as delivered?')">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="bi bi-check-circle"></i> Complete
                                </button>
                            </form>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="bi bi-file-earmark-text"></i></div>
                            <div class="empty-state-text">No manifests yet. Run optimization to generate shipping manifests.</div>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($manifests instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <div class="pagination-wrapper">{{ $manifests->links() }}</div>
    @endif
</div>
@endsection
