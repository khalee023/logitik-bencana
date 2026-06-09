@extends('layouts.app')
@section('title', 'Kebutuhan Daerah')
@section('page-title', 'Input & Monitor Kebutuhan')
@section('content')
<div class="glass-panel" style="padding: 1.5rem; margin-bottom: 2rem;">
    <h3 style="margin-top:0;">Log New Demand (Draft)</h3>
    <form action="{{ route('admin-daerah.demand.store') }}" method="POST" id="demandForm">
        @csrf
        <div style="margin-bottom: 1rem;">
            <label>Target Village</label>
            <select name="id_desa" class="form-control" required style="width: 100%; padding: 0.5rem; background: var(--color-bg); color: var(--color-text); border: 1px solid var(--color-glass-border);">
                @foreach($desaList as $desa)
                    <option value="{{ $desa->id }}">{{ $desa->nama }}</option>
                @endforeach
            </select>
        </div>

        <div id="items-container">
            <div class="item-row" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap; margin-bottom: 1rem;">
                <div style="flex: 1; min-width: 200px;">
                    <label>Relief Item (SKU)</label>
                    <select name="id_barang[]" class="form-control" required style="width: 100%; padding: 0.5rem; background: var(--color-bg); color: var(--color-text); border: 1px solid var(--color-glass-border);">
                        @foreach($barangList as $barang)
                            <option value="{{ $barang->id }}">{{ $barang->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="width: 150px;">
                    <label>Quantity</label>
                    <input type="number" name="kuantitas[]" class="form-control" min="1" required style="width: 100%; padding: 0.5rem; background: var(--color-bg); color: var(--color-text); border: 1px solid var(--color-glass-border);">
                </div>
                <button type="button" class="btn btn-danger btn-remove-item" style="padding: 0.5rem;" onclick="this.parentElement.remove()" disabled><i class="bi bi-trash"></i></button>
            </div>
        </div>

        <div style="display: flex; gap: 1rem; margin-top: 1rem;">
            <button type="button" class="btn btn-secondary" onclick="addDemandRow()"><i class="bi bi-plus-circle"></i> Add Another Item</button>
            <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1.5rem;"><i class="bi bi-save"></i> Save Draft (Batch)</button>
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

<div class="glass-panel" style="padding: 1.5rem;">
    <h3 style="margin-top:0;">Demand Nodes Registry</h3>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Batch ID</th><th>Village</th><th>Item</th><th>Qty</th><th>Urgency Score</th><th>Status</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($demands as $d)
                <tr>
                    <td><code>...{{ substr($d->kode_batch, -6) }}</code></td>
                    <td>{{ $d->desa->nama }}</td>
                    <td>{{ $d->barang->nama }}</td>
                    <td>{{ number_format($d->kuantitas) }}</td>
                    <td><strong class="text-danger">{{ $d->urgency_score }}</strong></td>
                    <td>
                        @if($d->status === 'Draft') <span class="badge badge-info">Draft</span>
                        @elseif($d->status === 'Queued') <span class="badge badge-warning">Queued</span>
                        @elseif($d->status === 'Manifested') <span class="badge badge-primary">Manifested</span>
                        @else <span class="badge badge-success">Fulfilled</span> @endif
                    </td>
                    <td>
                        @if($d->status === 'Draft')
                        <form action="{{ route('admin-daerah.demand.queue', $d->id) }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn-warning" style="padding: 0.2rem 0.5rem; font-size: 0.8rem;">Queue to Optimize</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
