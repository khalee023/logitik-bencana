@extends('layouts.app')
@section('title', 'Central Stock')
@section('page-title', 'Manage Central Stock')
@section('content')

<div class="glass-panel mb-xl">
    <div class="section-header">
        <h3><i class="bi bi-box-seam"></i> Update Stock Level</h3>
    </div>

    <form action="{{ route('admin-pusat.stok.update') }}" method="POST">
        @csrf
        <div class="form-row">
            <div class="form-group">
                <label>Distribution Center</label>
                <select name="id_pusat" class="form-control" required>
                    @foreach($pusatList as $p)
                        <option value="{{ $p->id }}">{{ $p->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Item (SKU)</label>
                <select name="id_barang" class="form-control" required>
                    @foreach($barangList as $b)
                        <option value="{{ $b->id }}">{{ $b->nama }} ({{ $b->kategori }})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="max-width: 160px;">
                <label>Total Quantity</label>
                <input type="number" name="total_kuantitas" class="form-control" min="0" required placeholder="0">
            </div>
            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
        </div>
    </form>
</div>

<div class="glass-panel">
    <div class="section-header">
        <h3>National Stock Levels</h3>
        <span class="section-count">{{ count($stok) }} entries</span>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Distribution Center</th><th>Item (SKU)</th><th>Category</th><th>Total Qty</th><th>Last Updated</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stok as $s)
                <tr>
                    <td>{{ $s->nama_pusat }}</td>
                    <td><strong>{{ $s->nama_barang }}</strong></td>
                    <td><span class="badge badge-info">{{ $s->kategori }}</span></td>
                    <td><strong class="text-accent text-mono">{{ number_format($s->total_kuantitas) }}</strong></td>
                    <td class="text-muted" style="font-size: 0.8rem;">{{ $s->updated_at }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5">
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="bi bi-stack"></i></div>
                            <div class="empty-state-text">No stock data recorded. Update stock above.</div>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
