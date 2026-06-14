@extends('layouts.app')
@section('title', 'Fleet Management')
@section('page-title', 'Manage Vehicle Fleet')
@section('content')

<div class="glass-panel mb-xl">
    <div class="section-header">
        <h3><i class="bi bi-truck"></i> Register New Vehicle</h3>
    </div>

    <form action="{{ route('admin-pusat.armada.store') }}" method="POST">
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
                <label>License Plate</label>
                <input type="text" name="plat_nomor" class="form-control" required placeholder="B 1234 XY" value="{{ old('plat_nomor') }}">
            </div>
            <div class="form-group" style="max-width: 160px;">
                <label>Max Weight (kg)</label>
                <input type="number" name="max_berat_kg" class="form-control" min="100" step="0.1" required placeholder="5000" value="{{ old('max_berat_kg') }}">
            </div>
            <div class="form-group" style="max-width: 160px;">
                <label>Max Volume (m³)</label>
                <input type="number" name="max_vol_m3" class="form-control" min="0.1" step="0.01" required placeholder="20.0" value="{{ old('max_vol_m3') }}">
            </div>
            <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Add Vehicle</button>
        </div>
    </form>
</div>

<div class="glass-panel">
    <div class="section-header">
        <h3>Fleet Registry</h3>
        <span class="section-count">{{ count($data) }} vehicles</span>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>License Plate</th><th>Distribution Center</th><th>Max Weight (kg)</th><th>Max Volume (m³)</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data as $d)
                <tr>
                    <td><code>{{ $d->plat_nomor }}</code></td>
                    <td>{{ $d->pusatDistribusi->nama }}</td>
                    <td class="text-mono">{{ number_format($d->max_berat_kg) }}</td>
                    <td class="text-mono">{{ number_format($d->max_vol_m3, 2) }}</td>
                    <td>
                        @if($d->status === 'Available')
                            <span class="badge badge-success"><span class="badge-dot"></span> Available</span>
                        @elseif($d->status === 'In-Transit')
                            <span class="badge badge-warning"><span class="badge-dot"></span> In-Transit</span>
                        @else
                            <span class="badge badge-danger">{{ $d->status }}</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5">
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="bi bi-truck"></i></div>
                            <div class="empty-state-text">No vehicles registered. Add one above.</div>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
