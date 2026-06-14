@extends('layouts.app')
@section('title', 'Distribution Centers')
@section('page-title', 'Manage Distribution Centers')
@section('content')

<div class="glass-panel mb-xl">
    <div class="section-header">
        <h3><i class="bi bi-building"></i> Register New Center</h3>
    </div>

    <form action="{{ route('admin-pusat.pusat-distribusi.store') }}" method="POST">
        @csrf
        <div class="form-row">
            <div class="form-group">
                <label>Region</label>
                <select name="id_kabupaten" class="form-control" required>
                    @foreach($kabupaten as $kab)
                        <option value="{{ $kab->id }}">{{ $kab->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Center Name</label>
                <input type="text" name="nama" class="form-control" required placeholder="Logistics Warehouse..." value="{{ old('nama') }}">
            </div>
            <div class="form-group" style="max-width: 140px;">
                <label>Latitude</label>
                <input type="number" name="lat" class="form-control" step="0.00000001" min="-90" max="90" required placeholder="-6.7321" value="{{ old('lat') }}">
            </div>
            <div class="form-group" style="max-width: 140px;">
                <label>Longitude</label>
                <input type="number" name="long_decimal" class="form-control" step="0.00000001" min="-180" max="180" required placeholder="107.0834" value="{{ old('long_decimal') }}">
            </div>
            <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Add Center</button>
        </div>
    </form>
</div>

<div class="glass-panel">
    <div class="section-header">
        <h3>Distribution Center Registry</h3>
        <span class="section-count">{{ count($data) }} centers</span>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th><th>Name</th><th>Region</th><th>Coordinates</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data as $d)
                <tr>
                    <td class="text-mono">{{ $d->id }}</td>
                    <td><strong>{{ $d->nama }}</strong></td>
                    <td>{{ $d->kabupaten->nama }}</td>
                    <td class="text-mono" style="font-size: 0.8rem;">{{ $d->lat }}, {{ $d->long_decimal }}</td>
                    <td>
                        @if($d->status_aktif)
                            <span class="badge badge-success"><span class="badge-dot"></span> Active</span>
                        @else
                            <span class="badge badge-danger">Inactive</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5">
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="bi bi-building"></i></div>
                            <div class="empty-state-text">No distribution centers yet. Add one above.</div>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
