@extends('layouts.app')
@section('title', 'Relief Item Catalog')
@section('page-title', 'Manage Relief Item Catalog')
@section('content')

<div class="glass-panel mb-xl">
    <div class="section-header">
        <h3><i class="bi bi-box"></i> Register New Relief Item</h3>
    </div>

    <form action="{{ route('admin-pusat.master-bantuan.store') }}" method="POST">
        @csrf
        <div class="form-row">
            <div class="form-group">
                <label>Item Name</label>
                <input type="text" name="nama" class="form-control" required placeholder="Emergency First Aid Kit" value="{{ old('nama') }}">
            </div>
            <div class="form-group" style="max-width: 160px;">
                <label>Category</label>
                <select name="kategori" class="form-control" required>
                    <option value="Medis">Medical</option>
                    <option value="Air">Water</option>
                    <option value="Ransum">Rations</option>
                    <option value="Tenda">Shelter</option>
                </select>
            </div>
            <div class="form-group" style="max-width: 130px;">
                <label>Weight (kg)</label>
                <input type="number" name="berat_kg" class="form-control" min="0.01" step="0.01" required placeholder="5.0" value="{{ old('berat_kg') }}">
            </div>
            <div class="form-group" style="max-width: 130px;">
                <label>Volume (m³)</label>
                <input type="number" name="volume_m3" class="form-control" min="0.0001" step="0.0001" required placeholder="0.05" value="{{ old('volume_m3') }}">
            </div>
            <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Add Item</button>
        </div>
    </form>
</div>

<div class="glass-panel">
    <div class="section-header">
        <h3>Relief Item Catalog (SKU)</h3>
        <span class="section-count">{{ count($data) }} items</span>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th><th>Item Name</th><th>Category</th><th>Weight (kg)</th><th>Volume (m³)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data as $d)
                <tr>
                    <td class="text-mono">{{ $d->id }}</td>
                    <td><strong>{{ $d->nama }}</strong></td>
                    <td><span class="badge badge-info">{{ $d->kategori }}</span></td>
                    <td class="text-mono">{{ $d->berat_kg }}</td>
                    <td class="text-mono">{{ $d->volume_m3 }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5">
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="bi bi-box"></i></div>
                            <div class="empty-state-text">No relief items in catalog. Add one above.</div>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
