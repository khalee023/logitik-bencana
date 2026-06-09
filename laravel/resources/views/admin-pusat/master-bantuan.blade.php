@extends('layouts.app')
@section('title', 'Master Bantuan')
@section('page-title', 'Kelola Master Bantuan')
@section('content')

<div class="glass-panel" style="padding: 1.5rem; margin-bottom: 2rem;">
    <h3 style="margin-top:0;"><i class="bi bi-box"></i> Tambah Item Bantuan</h3>
    
    @if(session('success'))
        <div style="background: rgba(25, 135, 84, 0.2); border-left: 4px solid var(--color-success); padding: 0.75rem 1rem; margin-bottom: 1rem; border-radius: var(--radius-sm); color: var(--color-text);">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div style="background: rgba(220, 53, 69, 0.2); border-left: 4px solid var(--color-danger); padding: 0.75rem 1rem; margin-bottom: 1rem; border-radius: var(--radius-sm); color: var(--color-text);">
            <ul style="margin: 0; padding-left: 1.5rem;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form action="{{ route('admin-pusat.master-bantuan.store') }}" method="POST">
        @csrf
        <div style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
            <div style="flex: 1; min-width: 200px;">
                <label>Nama Barang</label>
                <input type="text" name="nama" class="form-control" required placeholder="Paket P3K Darurat" value="{{ old('nama') }}" style="width: 100%; padding: 0.5rem; background: var(--color-bg); color: var(--color-text); border: 1px solid var(--color-glass-border);">
            </div>
            <div style="width: 150px;">
                <label>Kategori</label>
                <select name="kategori" class="form-control" required style="width: 100%; padding: 0.5rem; background: var(--color-bg); color: var(--color-text); border: 1px solid var(--color-glass-border);">
                    <option value="Medis">Medis</option>
                    <option value="Air">Air</option>
                    <option value="Ransum">Ransum</option>
                    <option value="Tenda">Tenda</option>
                </select>
            </div>
            <div style="width: 120px;">
                <label>Berat (kg)</label>
                <input type="number" name="berat_kg" class="form-control" min="0.01" step="0.01" required placeholder="5.0" value="{{ old('berat_kg') }}" style="width: 100%; padding: 0.5rem; background: var(--color-bg); color: var(--color-text); border: 1px solid var(--color-glass-border);">
            </div>
            <div style="width: 130px;">
                <label>Volume (m³)</label>
                <input type="number" name="volume_m3" class="form-control" min="0.0001" step="0.0001" required placeholder="0.05" value="{{ old('volume_m3') }}" style="width: 100%; padding: 0.5rem; background: var(--color-bg); color: var(--color-text); border: 1px solid var(--color-glass-border);">
            </div>
            <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1.5rem;"><i class="bi bi-plus-circle"></i> Tambah</button>
        </div>
    </form>
</div>

<div class="glass-panel" style="padding: 1.5rem;">
    <h3 style="margin-top:0;">Katalog Bantuan (SKU)</h3>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th><th>Nama Barang</th><th>Kategori</th><th>Berat (kg)</th><th>Volume (m3)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $d)
                <tr>
                    <td>{{ $d->id }}</td>
                    <td>{{ $d->nama }}</td>
                    <td><span class="badge badge-info">{{ $d->kategori }}</span></td>
                    <td>{{ $d->berat_kg }}</td>
                    <td>{{ $d->volume_m3 }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
