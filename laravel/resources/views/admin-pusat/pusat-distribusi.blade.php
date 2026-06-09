@extends('layouts.app')
@section('title', 'Pusat Distribusi')
@section('page-title', 'Kelola Pusat Distribusi')
@section('content')

<div class="glass-panel" style="padding: 1.5rem; margin-bottom: 2rem;">
    <h3 style="margin-top:0;"><i class="bi bi-building"></i> Tambah Pusat Distribusi</h3>
    
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

    <form action="{{ route('admin-pusat.pusat-distribusi.store') }}" method="POST">
        @csrf
        <div style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
            <div style="flex: 1; min-width: 180px;">
                <label>Kabupaten</label>
                <select name="id_kabupaten" class="form-control" required style="width: 100%; padding: 0.5rem; background: var(--color-bg); color: var(--color-text); border: 1px solid var(--color-glass-border);">
                    @foreach($kabupaten as $kab)
                        <option value="{{ $kab->id }}">{{ $kab->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div style="flex: 1; min-width: 180px;">
                <label>Nama Pusat</label>
                <input type="text" name="nama" class="form-control" required placeholder="Gudang Logistik..." value="{{ old('nama') }}" style="width: 100%; padding: 0.5rem; background: var(--color-bg); color: var(--color-text); border: 1px solid var(--color-glass-border);">
            </div>
            <div style="width: 140px;">
                <label>Latitude</label>
                <input type="number" name="lat" class="form-control" step="0.00000001" min="-90" max="90" required placeholder="-6.7321" value="{{ old('lat') }}" style="width: 100%; padding: 0.5rem; background: var(--color-bg); color: var(--color-text); border: 1px solid var(--color-glass-border);">
            </div>
            <div style="width: 140px;">
                <label>Longitude</label>
                <input type="number" name="long_decimal" class="form-control" step="0.00000001" min="-180" max="180" required placeholder="107.0834" value="{{ old('long_decimal') }}" style="width: 100%; padding: 0.5rem; background: var(--color-bg); color: var(--color-text); border: 1px solid var(--color-glass-border);">
            </div>
            <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1.5rem;"><i class="bi bi-plus-circle"></i> Tambah</button>
        </div>
    </form>
</div>

<div class="glass-panel" style="padding: 1.5rem;">
    <h3 style="margin-top:0;">Daftar Pusat Distribusi</h3>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th><th>Nama</th><th>Kabupaten</th><th>Kordinat</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $d)
                <tr>
                    <td>{{ $d->id }}</td>
                    <td>{{ $d->nama }}</td>
                    <td>{{ $d->kabupaten->nama }}</td>
                    <td>{{ $d->lat }}, {{ $d->long_decimal }}</td>
                    <td>{{ $d->status_aktif ? 'Aktif' : 'Inaktif' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
