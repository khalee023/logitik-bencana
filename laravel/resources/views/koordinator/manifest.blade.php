@extends('layouts.app')
@section('title', 'Riwayat Manifest')
@section('page-title', 'Riwayat Manifest Pengiriman')
@section('content')

@if(session('success'))
<div style="background: rgba(25, 135, 84, 0.2); border-left: 4px solid var(--color-success); padding: 0.75rem 1rem; margin-bottom: 1rem; border-radius: var(--radius-sm); color: var(--color-text);">
    {{ session('success') }}
</div>
@endif
@if(session('error'))
<div style="background: rgba(220, 53, 69, 0.2); border-left: 4px solid var(--color-danger); padding: 0.75rem 1rem; margin-bottom: 1rem; border-radius: var(--radius-sm); color: var(--color-text);">
    {{ session('error') }}
</div>
@endif

<div class="glass-panel" style="padding: 1.5rem;">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Kode Manifest (UUID)</th>
                    <th>Pusat Distribusi</th>
                    <th>Plat Armada</th>
                    <th>Waktu Berangkat</th>
                    <th>Waktu Tiba</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($manifests as $m)
                <tr>
                    <td><code>{{ $m->kode_manifest }}</code></td>
                    <td>{{ $m->pusatDistribusi->nama }}</td>
                    <td>{{ $m->armada->plat_nomor }}</td>
                    <td>{{ $m->waktu_berangkat }}</td>
                    <td>{{ $m->waktu_tiba ?? '—' }}</td>
                    <td>
                        @if($m->status === 'In-Transit')
                            <span class="badge badge-warning">In-Transit</span>
                        @elseif($m->status === 'Delivered')
                            <span class="badge badge-success">Delivered</span>
                        @else
                            <span class="badge badge-info">{{ $m->status }}</span>
                        @endif
                    </td>
                    <td>
                        @if($m->status === 'In-Transit')
                            <form action="{{ route('koordinator.manifest.complete', $m) }}" method="POST" style="display: inline;"
                                  onsubmit="return confirm('Tandai manifest ini sebagai selesai?')">
                                @csrf
                                <button type="submit" class="btn btn-primary" style="font-size: 0.75rem; padding: 0.25rem 0.75rem;">
                                    <i class="bi bi-check-circle"></i> Selesai
                                </button>
                            </form>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted">Belum ada manifest.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
