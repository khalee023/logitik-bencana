<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Disaster Logistics Command Center — Integrated ERP for disaster relief distribution management">
    <title>@yield('title', 'Command Center') — DLCC</title>

    <!-- Leaflet.js CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
        crossorigin="" />

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    @stack('styles')
</head>
<body>
    @auth
    <div class="app-shell">
        {{-- Sidebar --}}
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <div class="sidebar-brand-icon">🛡️</div>
                <span class="sidebar-brand-text">DLCC ERP</span>
            </div>

            <nav class="sidebar-nav">
                @php $role = auth()->user()->role; @endphp

                <div class="sidebar-section-label">Navigation</div>

                <a href="/" class="sidebar-link {{ request()->is('/') ? 'active' : '' }}" id="nav-public-map">
                    <span class="sidebar-link-icon"><i class="bi bi-globe-americas"></i></span>
                    <span class="sidebar-link-text">Public Map</span>
                </a>

                @if($role === 'Pusat')
                    <div class="sidebar-section-label">Central Admin</div>
                    <a href="{{ route('admin-pusat.dashboard') }}" class="sidebar-link {{ request()->is('admin-pusat') ? 'active' : '' }}" id="nav-pusat-dashboard">
                        <span class="sidebar-link-icon"><i class="bi bi-speedometer2"></i></span>
                        <span class="sidebar-link-text">Dashboard</span>
                    </a>
                    <a href="{{ route('admin-pusat.pusat-distribusi.index') }}" class="sidebar-link {{ request()->is('admin-pusat/pusat-distribusi*') ? 'active' : '' }}" id="nav-pusat-distribusi">
                        <span class="sidebar-link-icon"><i class="bi bi-building"></i></span>
                        <span class="sidebar-link-text">Distribution Centers</span>
                    </a>
                    <a href="{{ route('admin-pusat.master-bantuan.index') }}" class="sidebar-link {{ request()->is('admin-pusat/master-bantuan*') ? 'active' : '' }}" id="nav-master-bantuan">
                        <span class="sidebar-link-icon"><i class="bi bi-box-seam"></i></span>
                        <span class="sidebar-link-text">Relief Item Catalog</span>
                    </a>
                    <a href="{{ route('admin-pusat.stok.index') }}" class="sidebar-link {{ request()->is('admin-pusat/stok*') ? 'active' : '' }}" id="nav-stok">
                        <span class="sidebar-link-icon"><i class="bi bi-stack"></i></span>
                        <span class="sidebar-link-text">Central Stock</span>
                    </a>
                    <a href="{{ route('admin-pusat.armada.index') }}" class="sidebar-link {{ request()->is('admin-pusat/armada*') ? 'active' : '' }}" id="nav-armada">
                        <span class="sidebar-link-icon"><i class="bi bi-truck"></i></span>
                        <span class="sidebar-link-text">Fleet Management</span>
                    </a>
                    <a href="{{ route('admin-pusat.rute.index') }}" class="sidebar-link {{ request()->is('admin-pusat/rute*') ? 'active' : '' }}" id="nav-rute">
                        <span class="sidebar-link-icon"><i class="bi bi-signpost-2"></i></span>
                        <span class="sidebar-link-text">Road Network</span>
                    </a>
                @endif

                @if($role === 'Daerah')
                    <div class="sidebar-section-label">Regional Admin</div>
                    <a href="{{ route('admin-daerah.dashboard') }}" class="sidebar-link {{ request()->is('admin-daerah') ? 'active' : '' }}" id="nav-daerah-dashboard">
                        <span class="sidebar-link-icon"><i class="bi bi-speedometer2"></i></span>
                        <span class="sidebar-link-text">Dashboard</span>
                    </a>
                    <a href="{{ route('admin-daerah.demand.index') }}" class="sidebar-link {{ request()->is('admin-daerah/demand*') ? 'active' : '' }}" id="nav-demand">
                        <span class="sidebar-link-icon"><i class="bi bi-clipboard-data"></i></span>
                        <span class="sidebar-link-text">Demand Requests</span>
                    </a>
                @endif

                @if($role === 'SAR')
                    <div class="sidebar-section-label">Search & Rescue</div>
                    <a href="{{ route('sar.dashboard') }}" class="sidebar-link {{ request()->is('sar*') ? 'active' : '' }}" id="nav-sar-dashboard">
                        <span class="sidebar-link-icon"><i class="bi bi-shield-check"></i></span>
                        <span class="sidebar-link-text">Field Operations</span>
                    </a>
                @endif

                @if($role === 'Koor')
                    <div class="sidebar-section-label">Coordinator</div>
                    <a href="{{ route('koordinator.dashboard') }}" class="sidebar-link {{ request()->is('koordinator') ? 'active' : '' }}" id="nav-koordinator-dashboard">
                        <span class="sidebar-link-icon"><i class="bi bi-bullseye"></i></span>
                        <span class="sidebar-link-text">Command Center</span>
                    </a>
                    <a href="{{ route('koordinator.manifest') }}" class="sidebar-link {{ request()->is('koordinator/manifest*') ? 'active' : '' }}" id="nav-manifest">
                        <span class="sidebar-link-icon"><i class="bi bi-file-earmark-text"></i></span>
                        <span class="sidebar-link-text">Manifest History</span>
                    </a>
                @endif
            </nav>

            <div class="sidebar-footer">
                <div class="sidebar-user">
                    <div class="sidebar-user-avatar">
                        {{ strtoupper(substr(auth()->user()->nama, 0, 2)) }}
                    </div>
                    <div class="sidebar-user-info">
                        <div class="sidebar-user-name">{{ auth()->user()->nama }}</div>
                        <div class="sidebar-user-role">{{ auth()->user()->role }}</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="mt-md">
                    @csrf
                    <button type="submit" class="btn btn-ghost w-full" id="btn-logout">
                        <i class="bi bi-box-arrow-left"></i>
                        <span class="sidebar-link-text">Logout</span>
                    </button>
                </form>
            </div>
        </aside>

        {{-- Main Content --}}
        <main class="main-content">
            <header class="topbar">
                <h2 class="topbar-title">@yield('page-title', 'Dashboard')</h2>
                <div class="topbar-actions">
                    <span class="text-muted" style="font-size: 0.8rem;">
                        <i class="bi bi-clock"></i>
                        <span id="live-clock"></span>
                    </span>
                </div>
            </header>

            <div class="content-area">
                {{-- Flash Messages --}}
                @if(session('success'))
                    <div class="toast-container" id="flash-toast">
                        <div class="toast toast-success animate-slide-up">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <span>{{ session('success') }}</span>
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="toast-container" id="flash-toast">
                        <div class="toast toast-error animate-slide-up">
                            <i class="bi bi-exclamation-triangle-fill text-danger"></i>
                            <span>{{ session('error') }}</span>
                        </div>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
    @else
        @yield('content')
    @endauth

    {{-- Toast Container --}}
    <div class="toast-container" id="toast-container"></div>

    <!-- Leaflet.js -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin=""></script>

    <script>
        // CSRF Token for AJAX
        window.CSRF_TOKEN = '{{ csrf_token() }}';

        // Live Clock
        function updateClock() {
            const now = new Date();
            const el = document.getElementById('live-clock');
            if (el) {
                el.textContent = now.toLocaleTimeString('en-US', {
                    hour: '2-digit', minute: '2-digit', second: '2-digit'
                });
            }
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Auto-dismiss flash toasts
        setTimeout(() => {
            const toast = document.getElementById('flash-toast');
            if (toast) toast.style.display = 'none';
        }, 5000);

        // Toast helper
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast toast-${type} animate-slide-up`;
            const icon = type === 'success' ? 'check-circle-fill' :
                         type === 'error' ? 'exclamation-triangle-fill' : 'info-circle-fill';
            toast.innerHTML = `<i class="bi bi-${icon}"></i><span>${message}</span>`;
            container.appendChild(toast);
            setTimeout(() => toast.remove(), 5000);
        }

        // Fetch helper with CSRF
        async function fetchAPI(url, options = {}) {
            const defaults = {
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.CSRF_TOKEN,
                    'Accept': 'application/json',
                },
            };
            const config = { ...defaults, ...options, headers: { ...defaults.headers, ...options.headers } };
            const response = await fetch(url, config);
            if (!response.ok) {
                const error = await response.json().catch(() => ({ message: 'Request failed' }));
                throw new Error(error.message || `HTTP ${response.status}`);
            }
            return response.json();
        }
    </script>

    @stack('scripts')
</body>
</html>
