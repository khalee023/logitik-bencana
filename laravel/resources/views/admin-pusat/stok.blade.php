@extends('layouts.app')
@section('title', 'Stok Pusat')
@section('page-title', 'Kelola Stok Pusat')
@section('content')

<div class="glass-panel" style="padding: 1.5rem; margin-bottom: 2rem;">
    <h3 style="margin-top:0;"><i class="bi bi-box-seam"></i> Update Stok</h3>
    
    @if(session('success'))
        <div style="background: rgba(25, 135, 84, 0.2); border-left: 4px solid var(--color-success); padding: 0.75rem 1rem; margin-bottom: 1rem; border-radius: var(--radius-sm); color: var(--color-text);">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('admin-pusat.stok.update') }}" method="POST">
        @csrf
        <div style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
            <div style="flex: 1; min-width: 180px;">
                <label>Pusat Distribusi</label>
                <select name="id_pusat" class="form-control" required style="width: 100%; padding: 0.5rem; background: var(--color-bg); color: var(--color-text); border: 1px solid var(--color-glass-border);">
                    @foreach($pusatList as $p)
                        <option value="{{ $p->id }}">{{ $p->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div style="flex: 1; min-width: 180px;">
                <label>Barang (SKU)</label>
                <select name="id_barang" class="form-control" required style="width: 100%; padding: 0.5rem; background: var(--color-bg); color: var(--color-text); border: 1px solid var(--color-glass-border);">
                    @foreach($barangList as $b)
                        <option value="{{ $b->id }}">{{ $b->nama }} ({{ $b->kategori }})</option>
                    @endforeach
                </select>
            </div>
            <div style="width: 150px;">
                <label>Total Kuantitas</label>
                <input type="number" name="total_kuantitas" class="form-control" min="0" required placeholder="0" style="width: 100%; padding: 0.5rem; background: var(--color-bg); color: var(--color-text); border: 1px solid var(--color-glass-border);">
            </div>
            <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1.5rem;"><i class="bi bi-save"></i> Simpan</button>
        </div>
    </form>
</div>

<div class="glass-panel" style="padding: 1.5rem;">
    <h3 style="margin-top:0;">Data Stok Nasional</h3>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Pusat Distribusi</th><th>Barang (SKU)</th><th>Kategori</th><th>Total Kuantitas</th><th>Update Terakhir</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stok as $s)
                <tr>
                    <td>{{ $s->nama_pusat }}</td>
                    <td>{{ $s->nama_barang }}</td>
                    <td>{{ $s->kategori }}</td>
                    <td><strong class="text-info">{{ number_format($s->total_kuantitas) }}</strong></td>
                    <td>{{ $s->updated_at }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
