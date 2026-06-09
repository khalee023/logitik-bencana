@extends('layouts.app')

@section('title', 'SAR Field Ops Dashboard')
@section('page-title', 'Manajemen Rute Lapangan')

@section('content')
<div class="stats-grid">
    <div class="stat-card glass-panel">
        <div class="stat-label">Total Segmen Rute</div>
        <div class="stat-value text-info">{{ $totalRute }}</div>
    </div>
    <div class="stat-card glass-panel">
        <div class="stat-label">Rute Terbuka (Aman)</div>
        <div class="stat-value text-success">{{ $ruteTerbuka }}</div>
    </div>
    <div class="stat-card glass-panel">
        <div class="stat-label">Rute Terblokir (Evakuasi)</div>
        <div class="stat-value text-danger" id="stat-terblokir">{{ $ruteTerblokir }}</div>
    </div>
</div>

<div class="glass-panel" style="margin-top: 2rem; padding: 1.5rem;">
    <h3 style="margin-top:0; border-bottom: 1px solid var(--color-glass-border); padding-bottom: 0.5rem;">Tinjauan Jaringan Jalan</h3>
    
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID Rute</th>
                    <th>Titik Asal</th>
                    <th>Titik Tujuan</th>
                    <th>Jarak (KM)</th>
                    <th>Status Akses</th>
                    <th>Aksi Lapangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rutes as $r)
                <tr>
                    <td>{{ $r->id }}</td>
                    <td>Node #{{ $r->id_titik_asal }}</td>
                    <td>Node #{{ $r->id_titik_tujuan }}</td>
                    <td>{{ number_format($r->jarak_km, 2) }} km</td>
                    <td id="status-col-{{ $r->id }}">
                        @if($r->status_akses_terbuka)
                            <span class="badge badge-success">TERBUKA</span>
                        @else
                            <span class="badge badge-danger">TERBLOKIR</span>
                        @endif
                    </td>
                    <td>
                        <button class="btn btn-ghost" onclick="toggleRoute({{ $r->id }})">
                            <i class="bi bi-arrow-left-right"></i> Toggle Akses
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align: center;" class="text-muted">Data rute kosong.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="glass-panel" style="margin-top: 2rem; padding: 1.5rem;">
    <h3 style="margin-top:0; border-bottom: 1px solid var(--color-glass-border); padding-bottom: 0.5rem;">Manajemen Status Desa</h3>
    
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Desa</th>
                    <th>Populasi</th>
                    <th>Korban Selamat</th>
                    <th>Orang Sakit</th>
                    <th>Infrastruktur Rusak (%)</th>
                    <th>Status Aman</th>
                    <th>Status Isolasi</th>
                    <th>Aksi Lapangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($desaList as $d)
                <tr>
                    <td>{{ $d->nama }}</td>
                    <td>{{ number_format($d->populasi) }}</td>
                    <td>{{ number_format($d->korban_selamat) }}</td>
                    <td>{{ number_format($d->jumlah_orang_sakit) }}</td>
                    <td>{{ $d->persentase_infrastruktur_rusak }}%</td>
                    <td id="desa-aman-{{ $d->id }}">
                        @if($d->status_aman)
                            <span class="badge badge-success">AMAN</span>
                        @else
                            <span class="badge badge-danger">TERDAMPAK</span>
                        @endif
                    </td>
                    <td id="desa-isolasi-{{ $d->id }}">
                        @if($d->status_isolasi)
                            <span class="badge badge-warning">TERISOLASI</span>
                        @else
                            <span class="badge badge-info">TERHUBUNG</span>
                        @endif
                    </td>
                    <td>
                        <div style="display: flex; gap: 0.3rem; flex-wrap: wrap;">
                            <button class="btn btn-ghost" onclick="toggleDesaStatus({{ $d->id }}, 'status_aman')" style="font-size: 0.75rem; padding: 0.2rem 0.5rem;">
                                <i class="bi bi-shield-check"></i> Toggle Aman
                            </button>
                            <button class="btn btn-ghost" onclick="toggleDesaStatus({{ $d->id }}, 'status_isolasi')" style="font-size: 0.75rem; padding: 0.2rem 0.5rem;">
                                <i class="bi bi-geo-alt"></i> Toggle Isolasi
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align: center;" class="text-muted">Data desa kosong.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
async function toggleRoute(id) {
    if(!confirm('Anda yakin ingin mengubah status rute ini? Perubahan akan langsung memengaruhi kalkulasi algoritma optimasi rute logistik!')) return;

    try {
        const result = await fetchAPI('{{ route("api.rute.toggle") }}', {
            method: 'POST',
            body: JSON.stringify({ id_rute: id })
        });
        
        showToast(result.message, 'success');
        
        // Update DOM
        const col = document.getElementById('status-col-' + id);
        if (result.rute.status_akses_terbuka) {
            col.innerHTML = '<span class="badge badge-success">TERBUKA</span>';
        } else {
            col.innerHTML = '<span class="badge badge-danger">TERBLOKIR</span>';
        }
        
        setTimeout(() => window.location.reload(), 1500);

    } catch (err) {
        showToast(err.message, 'error');
    }
}

async function toggleDesaStatus(id, field) {
    const fieldLabel = field === 'status_aman' ? 'Status Aman' : 'Status Isolasi';
    if(!confirm(`Anda yakin ingin mengubah ${fieldLabel} desa ini?`)) return;

    try {
        // Get current value and toggle it
        const currentBadge = document.getElementById(`desa-${field === 'status_aman' ? 'aman' : 'isolasi'}-${id}`);
        const currentlyTrue = currentBadge.querySelector('.badge-success, .badge-warning') !== null;
        
        const body = { id_desa: id };
        body[field] = !currentlyTrue;

        const result = await fetchAPI('{{ route("api.desa.status") }}', {
            method: 'POST',
            body: JSON.stringify(body)
        });
        
        showToast(result.message, 'success');
        
        // Update DOM for status_aman
        if (field === 'status_aman') {
            const col = document.getElementById('desa-aman-' + id);
            col.innerHTML = result.desa.status_aman
                ? '<span class="badge badge-success">AMAN</span>'
                : '<span class="badge badge-danger">TERDAMPAK</span>';
        }
        // Update DOM for status_isolasi
        if (field === 'status_isolasi') {
            const col = document.getElementById('desa-isolasi-' + id);
            col.innerHTML = result.desa.status_isolasi
                ? '<span class="badge badge-warning">TERISOLASI</span>'
                : '<span class="badge badge-info">TERHUBUNG</span>';
        }

    } catch (err) {
        showToast(err.message, 'error');
    }
}
</script>
@endpush

