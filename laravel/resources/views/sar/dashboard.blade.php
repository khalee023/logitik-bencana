@extends('layouts.app')

@section('title', 'SAR Field Ops Dashboard')
@section('page-title', 'Field Route Management')

@section('content')
<div class="stats-grid">
    <div class="stat-card glass-panel accent-info">
        <div class="stat-card-icon primary"><i class="bi bi-signpost-2"></i></div>
        <div class="stat-label">Total Route Segments</div>
        <div class="stat-value text-info">{{ $totalRute }}</div>
    </div>
    <div class="stat-card glass-panel accent-success">
        <div class="stat-card-icon success"><i class="bi bi-check-circle"></i></div>
        <div class="stat-label">Open Routes (Accessible)</div>
        <div class="stat-value text-success">{{ $ruteTerbuka }}</div>
    </div>
    <div class="stat-card glass-panel accent-danger">
        <div class="stat-card-icon danger"><i class="bi bi-x-octagon"></i></div>
        <div class="stat-label">Blocked Routes</div>
        <div class="stat-value text-danger" id="stat-terblokir">{{ $ruteTerblokir }}</div>
    </div>
</div>

<div class="glass-panel mt-xl">
    <div class="section-header">
        <h3><i class="bi bi-diagram-3"></i> Road Network Overview</h3>
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
                    <th>Field Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rutes as $r)
                <tr>
                    <td class="text-mono">{{ $r->id }}</td>
                    <td>Node #{{ $r->id_titik_asal }}</td>
                    <td>Node #{{ $r->id_titik_tujuan }}</td>
                    <td class="text-mono">{{ number_format($r->jarak_km, 2) }} km</td>
                    <td id="status-col-{{ $r->id }}">
                        @if($r->status_akses_terbuka)
                            <span class="badge badge-success"><span class="badge-dot"></span> OPEN</span>
                        @else
                            <span class="badge badge-danger"><span class="badge-dot"></span> BLOCKED</span>
                        @endif
                    </td>
                    <td>
                        <button class="btn btn-outline btn-sm" onclick="toggleRoute({{ $r->id }})">
                            <i class="bi bi-arrow-left-right"></i> Toggle Access
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="bi bi-signpost-2"></i></div>
                            <div class="empty-state-text">No route data available.</div>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="glass-panel mt-xl">
    <div class="section-header">
        <h3><i class="bi bi-houses"></i> Village Status Management</h3>
        <span class="section-count">{{ count($desaList) }} villages</span>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Village</th>
                    <th>Population</th>
                    <th>Survivors</th>
                    <th>Sick Count</th>
                    <th>Infrastructure Damage</th>
                    <th>Safety Status</th>
                    <th>Isolation Status</th>
                    <th>Field Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($desaList as $d)
                <tr>
                    <td><strong>{{ $d->nama }}</strong></td>
                    <td class="text-mono">{{ number_format($d->populasi) }}</td>
                    <td class="text-mono">{{ number_format($d->korban_selamat) }}</td>
                    <td class="text-mono">{{ number_format($d->jumlah_orang_sakit) }}</td>
                    <td>
                        <div class="urgency-indicator">
                            @php $dmg = $d->persentase_infrastruktur_rusak; @endphp
                            <span class="urgency-dot {{ $dmg >= 75 ? 'critical' : ($dmg >= 50 ? 'high' : ($dmg >= 25 ? 'medium' : 'low')) }}"></span>
                            {{ $dmg }}%
                        </div>
                    </td>
                    <td id="desa-aman-{{ $d->id }}">
                        @if($d->status_aman)
                            <span class="badge badge-success">SAFE</span>
                        @else
                            <span class="badge badge-danger">AFFECTED</span>
                        @endif
                    </td>
                    <td id="desa-isolasi-{{ $d->id }}">
                        @if($d->status_isolasi)
                            <span class="badge badge-warning">ISOLATED</span>
                        @else
                            <span class="badge badge-info">CONNECTED</span>
                        @endif
                    </td>
                    <td>
                        <div class="flex gap-sm" style="flex-wrap: wrap;">
                            <button class="btn btn-outline btn-sm" onclick="toggleDesaStatus({{ $d->id }}, 'status_aman')" style="font-size: 0.7rem;">
                                <i class="bi bi-shield-check"></i> Safety
                            </button>
                            <button class="btn btn-outline btn-sm" onclick="toggleDesaStatus({{ $d->id }}, 'status_isolasi')" style="font-size: 0.7rem;">
                                <i class="bi bi-geo-alt"></i> Isolation
                            </button>
                            <button class="btn btn-primary btn-sm" onclick="openMetricsModal({{ $d->id }}, '{{ addslashes($d->nama) }}', {{ $d->populasi }}, {{ $d->korban_selamat }}, {{ $d->jumlah_orang_sakit }}, {{ $d->persentase_infrastruktur_rusak }})" style="font-size: 0.7rem;">
                                <i class="bi bi-pencil"></i> Metrics
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8">
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="bi bi-houses"></i></div>
                            <div class="empty-state-text">No village data available.</div>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Edit Metrics -->
<div id="metricsModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div class="modal-content glass-panel" style="width: 400px; padding: 20px; border-radius: 8px;">
        <div class="flex" style="justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 style="margin:0;"><i class="bi bi-pencil-square"></i> Edit Metrics <span id="modalDesaName"></span></h3>
            <button class="btn btn-ghost" onclick="closeMetricsModal()"><i class="bi bi-x-lg"></i></button>
        </div>
        <form id="metricsForm" onsubmit="submitMetricsForm(event)">
            <input type="hidden" id="modal_id_desa" name="id_desa">
            <div class="form-group mb-md">
                <label>Populasi (Jiwa)</label>
                <input type="number" class="form-control" id="modal_populasi" name="populasi" required min="0">
            </div>
            <div class="form-group mb-md">
                <label>Korban Selamat</label>
                <input type="number" class="form-control" id="modal_korban_selamat" name="korban_selamat" required min="0">
            </div>
            <div class="form-group mb-md">
                <label>Jumlah Orang Sakit</label>
                <input type="number" class="form-control" id="modal_jumlah_orang_sakit" name="jumlah_orang_sakit" required min="0">
            </div>
            <div class="form-group mb-md">
                <label>Kerusakan Infrastruktur (%)</label>
                <input type="number" class="form-control" id="modal_persentase_infrastruktur_rusak" name="persentase_infrastruktur_rusak" required min="0" max="100" step="0.01">
            </div>
            <div class="flex" style="justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn btn-outline" onclick="closeMetricsModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
async function toggleRoute(id) {
    if(!confirm('Are you sure you want to change the access status of this route? This change will immediately affect the logistics optimization algorithm!')) return;

    try {
        const result = await fetchAPI('{{ route("api.rute.toggle") }}', {
            method: 'POST',
            body: JSON.stringify({ id_rute: id })
        });

        showToast(result.message, 'success');

        const col = document.getElementById('status-col-' + id);
        if (result.rute.status_akses_terbuka) {
            col.innerHTML = '<span class="badge badge-success"><span class="badge-dot"></span> OPEN</span>';
        } else {
            col.innerHTML = '<span class="badge badge-danger"><span class="badge-dot"></span> BLOCKED</span>';
        }

        setTimeout(() => window.location.reload(), 1500);

    } catch (err) {
        showToast(err.message, 'error');
    }
}

async function toggleDesaStatus(id, field) {
    const fieldLabel = field === 'status_aman' ? 'Safety Status' : 'Isolation Status';
    if(!confirm(`Are you sure you want to change the ${fieldLabel} of this village?`)) return;

    try {
        const currentBadge = document.getElementById(`desa-${field === 'status_aman' ? 'aman' : 'isolasi'}-${id}`);
        const currentlyTrue = currentBadge.querySelector('.badge-success, .badge-warning') !== null;

        const body = { id_desa: id };
        body[field] = !currentlyTrue;

        const result = await fetchAPI('{{ route("api.desa.status") }}', {
            method: 'POST',
            body: JSON.stringify(body)
        });

        showToast(result.message, 'success');

        if (field === 'status_aman') {
            const col = document.getElementById('desa-aman-' + id);
            col.innerHTML = result.desa.status_aman
                ? '<span class="badge badge-success">SAFE</span>'
                : '<span class="badge badge-danger">AFFECTED</span>';
        }
        if (field === 'status_isolasi') {
            const col = document.getElementById('desa-isolasi-' + id);
            col.innerHTML = result.desa.status_isolasi
                ? '<span class="badge badge-warning">ISOLATED</span>'
                : '<span class="badge badge-info">CONNECTED</span>';
        }

    } catch (err) {
        showToast(err.message, 'error');
    }
}

function openMetricsModal(id, nama, pop, surv, sick, infra) {
    document.getElementById('modal_id_desa').value = id;
    document.getElementById('modalDesaName').textContent = '- ' + nama;
    document.getElementById('modal_populasi').value = pop;
    document.getElementById('modal_korban_selamat').value = surv;
    document.getElementById('modal_jumlah_orang_sakit').value = sick;
    document.getElementById('modal_persentase_infrastruktur_rusak').value = infra;
    
    document.getElementById('metricsModal').style.display = 'flex';
}

function closeMetricsModal() {
    document.getElementById('metricsModal').style.display = 'none';
}

async function submitMetricsForm(e) {
    e.preventDefault();
    const id = document.getElementById('modal_id_desa').value;
    const pop = document.getElementById('modal_populasi').value;
    const surv = document.getElementById('modal_korban_selamat').value;
    const sick = document.getElementById('modal_jumlah_orang_sakit').value;
    const infra = document.getElementById('modal_persentase_infrastruktur_rusak').value;

    try {
        const result = await fetchAPI('{{ route("api.desa.metrics") }}', {
            method: 'POST',
            body: JSON.stringify({
                id_desa: id,
                populasi: pop,
                korban_selamat: surv,
                jumlah_orang_sakit: sick,
                persentase_infrastruktur_rusak: infra
            })
        });

        showToast(result.message, 'success');
        closeMetricsModal();
        setTimeout(() => window.location.reload(), 1500);
    } catch (err) {
        showToast(err.message, 'error');
    }
}
</script>
@endpush
