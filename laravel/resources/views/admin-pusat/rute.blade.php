@extends('layouts.app')
@section('title', 'Road Network Management')
@section('page-title', 'Road Network Management')
@section('content')

<div class="glass-panel mb-xl">
    <div class="section-header">
        <h3><i class="bi bi-signpost-2"></i> Add New Route</h3>
    </div>

    <form action="{{ route('admin-pusat.rute.store') }}" method="POST">
        @csrf
        <div class="form-row mb-md">
            <div class="form-group" style="flex: 1;">
                <label>Origin Node</label>
                <select name="id_titik_asal" class="form-control" required>
                    <option value="">-- Select Origin --</option>
                    <optgroup label="Pusat Distribusi (Depot)">
                        @foreach($pusatDistribusi as $p)
                            <option value="{{ $p->id }}">{{ $p->nama }} (ID: {{ $p->id }})</option>
                        @endforeach
                    </optgroup>
                    <optgroup label="Desa (Village)">
                        @foreach($desa as $d)
                            <option value="{{ $d->id }}">{{ $d->nama }} (ID: {{ $d->id }})</option>
                        @endforeach
                    </optgroup>
                </select>
            </div>
            
            <div class="form-group" style="flex: 1;">
                <label>Destination Node</label>
                <select name="id_titik_tujuan" class="form-control" required>
                    <option value="">-- Select Destination --</option>
                    <optgroup label="Pusat Distribusi (Depot)">
                        @foreach($pusatDistribusi as $p)
                            <option value="{{ $p->id }}">{{ $p->nama }} (ID: {{ $p->id }})</option>
                        @endforeach
                    </optgroup>
                    <optgroup label="Desa (Village)">
                        @foreach($desa as $d)
                            <option value="{{ $d->id }}">{{ $d->nama }} (ID: {{ $d->id }})</option>
                        @endforeach
                    </optgroup>
                </select>
            </div>
            
            <div class="form-group" style="max-width: 150px;">
                <label>Distance (km)</label>
                <input type="number" name="jarak_km" class="form-control" step="0.1" min="0.1" required>
            </div>
            
            <div class="form-group" style="max-width: 150px;">
                <label>Access Status</label>
                <select name="status_akses_terbuka" class="form-control" required>
                    <option value="1">OPEN</option>
                    <option value="0">BLOCKED</option>
                </select>
            </div>
        </div>

        <div class="flex" style="justify-content: flex-end;">
            <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Add Route Segment</button>
        </div>
    </form>
</div>

<div class="glass-panel">
    <div class="section-header">
        <h3><i class="bi bi-diagram-3"></i> Existing Routes</h3>
        <span class="section-count">{{ count($rutes) }} segments</span>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Route ID</th>
                    <th>Origin Node</th>
                    <th>Destination Node</th>
                    <th>Distance</th>
                    <th>Access Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rutes as $r)
                <tr>
                    <td class="text-mono">{{ $r->id }}</td>
                    <td>
                        @php $asal = $r->resolveTitikAsal(); @endphp
                        {{ $asal ? $asal->nama : 'Node #'.$r->id_titik_asal }}
                    </td>
                    <td>
                        @php $tujuan = $r->resolveTitikTujuan(); @endphp
                        {{ $tujuan ? $tujuan->nama : 'Node #'.$r->id_titik_tujuan }}
                    </td>
                    <td class="text-mono">{{ number_format($r->jarak_km, 2) }} km</td>
                    <td>
                        @if($r->status_akses_terbuka)
                            <span class="badge badge-success"><span class="badge-dot"></span> OPEN</span>
                        @else
                            <span class="badge badge-danger"><span class="badge-dot"></span> BLOCKED</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5">
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="bi bi-signpost-2"></i></div>
                            <div class="empty-state-text">No route segments found in the network.</div>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
