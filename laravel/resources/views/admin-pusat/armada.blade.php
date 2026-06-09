@extends('layouts.app')
@section('title', 'Armada')
@section('page-title', 'Kelola Armada Kendaraan')
@section('content')

<div class="glass-panel" style="padding: 1.5rem; margin-bottom: 2rem;">
    <h3 style="margin-top:0;"><i class="bi bi-truck"></i> Registrasi Kendaraan Baru</h3>
    
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

    <form action="{{ route('admin-pusat.armada.store') }}" method="POST">
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
            <div style="flex: 1; min-width: 150px;">
                <label>Plat Nomor</label>
                <input type="text" name="plat_nomor" class="form-control" required placeholder="B 1234 XY" value="{{ old('plat_nomor') }}" style="width: 100%; padding: 0.5rem; background: var(--color-bg); color: var(--color-text); border: 1px solid var(--color-glass-border);">
            </div>
            <div style="width: 150px;">
                <label>Max Berat (kg)</label>
                <input type="number" name="max_berat_kg" class="form-control" min="100" step="0.1" required placeholder="5000" value="{{ old('max_berat_kg') }}" style="width: 100%; padding: 0.5rem; background: var(--color-bg); color: var(--color-text); border: 1px solid var(--color-glass-border);">
            </div>
            <div style="width: 150px;">
                <label>Max Volume (m³)</label>
                <input type="number" name="max_vol_m3" class="form-control" min="0.1" step="0.01" required placeholder="20.0" value="{{ old('max_vol_m3') }}" style="width: 100%; padding: 0.5rem; background: var(--color-bg); color: var(--color-text); border: 1px solid var(--color-glass-border);">
            </div>
            <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1.5rem;"><i class="bi bi-plus-circle"></i> Tambah</button>
        </div>
    </form>
</div>

<div class="glass-panel" style="padding: 1.5rem;">
    <h3 style="margin-top:0;">Daftar Armada</h3>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Plat Nomor</th><th>Pusat Distribusi</th><th>Max Berat (kg)</th><th>Max Volume (m3)</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $d)
                <tr>
                    <td><code>{{ $d->plat_nomor }}</code></td>
                    <td>{{ $d->pusatDistribusi->nama }}</td>
                    <td>{{ number_format($d->max_berat_kg) }}</td>
                    <td>{{ number_format($d->max_vol_m3, 2) }}</td>
                    <td>
                        @if($d->status === 'Available')
                            <span class="badge badge-success">Available</span>
                        @else
                            <span class="badge badge-warning">{{ $d->status }}</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
