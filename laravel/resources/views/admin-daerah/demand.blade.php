@extends('layouts.app')
@section('title', 'Demand Requests')
@section('page-title', 'Submit & Monitor Demands')
@section('content')

<div class="glass-panel mb-xl">
    <div class="section-header">
        <h3><i class="bi bi-pencil-square"></i> Log New Demand (Draft)</h3>
    </div>

    <form action="{{ route('admin-daerah.demand.store') }}" method="POST" id="demandForm">
        @csrf
        <div class="form-group">
            <label>Target Village</label>
            <select name="id_desa" class="form-control" required>
                @foreach($desaList as $desa)
                    <option value="{{ $desa->id }}">{{ $desa->nama }}</option>
                @endforeach
            </select>
        </div>

        <div id="items-container">
            <div class="item-row form-row mb-md">
                <div class="form-group">
                    <label>Relief Item (SKU)</label>
                    <select name="id_barang[]" class="form-control" required>
                        @foreach($barangList as $barang)
                            <option value="{{ $barang->id }}">{{ $barang->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="max-width: 160px;">
                    <label>Quantity</label>
                    <input type="number" name="kuantitas[]" class="form-control" min="1" required placeholder="100">
                </div>
                <button type="button" class="btn btn-danger btn-sm btn-remove-item" onclick="this.parentElement.remove()" disabled><i class="bi bi-trash"></i></button>
            </div>
        </div>

        <div class="flex gap-md mt-lg">
            <button type="button" class="btn btn-outline" onclick="addDemandRow()"><i class="bi bi-plus-circle"></i> Add Another Item</button>
            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Draft (Batch)</button>
        </div>
    </form>

    <script>
        function addDemandRow() {
            const container = document.getElementById('items-container');
            const firstRow = container.querySelector('.item-row');
            const newRow = firstRow.cloneNode(true);
            newRow.querySelector('input').value = '';
            newRow.querySelector('.btn-remove-item').disabled = false;
            container.appendChild(newRow);
        }
    </script>
</div>

<div class="glass-panel">
    <div class="section-header">
        <h3><i class="bi bi-list-check"></i> Demand Registry</h3>
        <span class="section-count">{{ $demands instanceof \Illuminate\Pagination\LengthAwarePaginator ? $demands->total() : count($demands) }} records</span>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Batch ID</th><th>Village</th><th>Item</th><th>Qty</th><th>Urgency</th><th>Status</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($demands as $d)
                <tr>
                    <td><code class="text-mono">...{{ substr($d->kode_batch, -6) }}</code></td>
                    <td><strong>{{ $d->desa->nama }}</strong></td>
                    <td>{{ $d->barang->nama }}</td>
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
                    <td>
                        @if($d->status === 'Draft') <span class="badge badge-draft">Draft</span>
                        @elseif($d->status === 'Queued') <span class="badge badge-queued"><span class="badge-dot"></span> Queued</span>
                        @elseif($d->status === 'Manifested') <span class="badge badge-manifested">Manifested</span>
                        @elseif($d->status === 'Delivered') <span class="badge badge-success"><span class="badge-dot"></span> Delivered</span>
                        @else <span class="badge badge-fulfilled"><span class="badge-dot"></span> Fulfilled</span> @endif
                    </td>
                    <td>
                        @if($d->status === 'Draft')
                        <form action="{{ route('admin-daerah.demand.queue', $d->id) }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn-warning btn-sm">
                                <i class="bi bi-send"></i> Queue
                            </button>
                        </form>
                        @elseif($d->status === 'Delivered')
                        <form action="{{ route('admin-daerah.demand.confirm', $d->id) }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="bi bi-check-circle"></i> Konfirmasi
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
                            <div class="empty-state-icon"><i class="bi bi-clipboard-data"></i></div>
                            <div class="empty-state-text">No demand requests yet. Create one above.</div>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($demands instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <div class="pagination-wrapper">{{ $demands->links() }}</div>
    @endif
</div>
@endsection
