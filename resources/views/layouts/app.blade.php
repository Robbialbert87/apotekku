<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        (function () {
            try {
                var theme = localStorage.getItem('hms-theme') === 'dark' ? 'dark' : 'light';
                document.documentElement.setAttribute('data-theme', theme);
            } catch (e) {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>
    <title>@yield('title', 'Dashboard') · {{ config('apotek.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body>
<a href="#main-content" class="skip-link">Lewati ke konten</a>

<div class="app-shell">
    <aside class="sidebar" aria-label="Menu utama">
        <a class="sidebar__brand" href="{{ route('dashboard') }}" aria-label="{{ config('apotek.name') }}">
            <div class="sidebar__brand-mark">A</div>
            <span class="sidebar__brand-text">{{ config('apotek.name') }}</span>
        </a>

        <nav class="sidebar__nav">
            <div class="sidebar__section-label">Ringkasan</div>
            <ul class="nav-item-list">
                @if (auth()->user()->hasPermission('dashboard.view'))
                    <li class="nav-item">
                        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">
                            <i class="bi bi-grid-1x2-fill"></i><span class="nav-link__label">Dashboard</span>
                        </a>
                    </li>
                @endif
            </ul>

            <div class="sidebar__section-label">Transaksi</div>
            <ul class="nav-item-list">
                @if (auth()->user()->hasPermission('supplier.view'))
                    <li class="nav-item">
                        <a href="{{ route('suppliers.index') }}" class="nav-link {{ request()->routeIs('suppliers.*') ? 'is-active' : '' }}">
                            <i class="bi bi-truck"></i><span class="nav-link__label">Supplier</span>
                        </a>
                    </li>
                @endif
                @if (auth()->user()->hasPermission('invoice.view'))
                    <li class="nav-item">
                        <a href="{{ route('invoices.index') }}" class="nav-link {{ request()->routeIs('invoices.*') ? 'is-active' : '' }}">
                            <i class="bi bi-receipt"></i><span class="nav-link__label">Invoice</span>
                        </a>
                    </li>
                @endif
                @if (auth()->user()->hasPermission('payment.view'))
                    <li class="nav-item">
                        <a href="{{ route('payments.index') }}" class="nav-link {{ request()->routeIs('payments.*') ? 'is-active' : '' }}">
                            <i class="bi bi-cash-stack"></i><span class="nav-link__label">Pembayaran</span>
                        </a>
                    </li>
                @endif
                @if (auth()->user()->hasPermission('plan.view'))
                    <li class="nav-item">
                        <a href="{{ route('plans.index') }}" class="nav-link {{ request()->routeIs('plans.*') ? 'is-active' : '' }}">
                            <i class="bi bi-calendar2-check"></i><span class="nav-link__label">Rencana Pembayaran</span>
                        </a>
                    </li>
                @endif
                @if (auth()->user()->hasPermission('report.view'))
                    <li class="nav-item">
                        <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'is-active' : '' }}">
                            <i class="bi bi-graph-up-arrow"></i><span class="nav-link__label">Laporan</span>
                        </a>
                    </li>
                @endif
            </ul>

            @if (auth()->user()->hasPermission('user.view') || auth()->user()->hasPermission('setting.view'))
                <div class="sidebar__section-label">Sistem</div>
                <ul class="nav-item-list">
                    @if (auth()->user()->hasPermission('user.view'))
                        <li class="nav-item">
                            <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*', 'roles.*') ? 'is-active' : '' }}">
                                <i class="bi bi-people-fill"></i><span class="nav-link__label">Pengguna</span>
                            </a>
                        </li>
                    @endif
                    @if (auth()->user()->hasPermission('setting.view'))
                        <li class="nav-item">
                            <a href="{{ route('settings.edit') }}" class="nav-link {{ request()->routeIs('settings.*', 'activity.*') ? 'is-active' : '' }}">
                                <i class="bi bi-gear-fill"></i><span class="nav-link__label">Pengaturan</span>
                            </a>
                        </li>
                    @endif
                </ul>
            @endif
        </nav>
    </aside>
    <div class="sidebar-backdrop" data-sidebar-close></div>

    <div class="main">
        <header class="topbar">
            <button class="icon-btn sidebar-toggle" data-sidebar-toggle aria-label="Buka menu"><i class="bi bi-list" style="font-size:1.3rem;"></i></button>
            <button class="icon-btn d-none d-lg-inline-flex" data-sidebar-collapse data-tooltip="Ciutkan sidebar" aria-label="Ciutkan sidebar"><i class="bi bi-layout-sidebar"></i></button>

            @if (auth()->user()->hasPermission('invoice.view'))
                <form action="{{ route('invoices.index') }}" method="GET" class="topbar__search" role="search">
                    <i class="bi bi-search"></i>
                    <input type="search" name="search" class="form-control-custom" placeholder="Cari invoice atau supplier..." aria-label="Cari" value="{{ request()->routeIs('invoices.index') ? request('search') : '' }}">
                </form>
            @endif

            <div class="topbar__actions">
                <button class="icon-btn" data-theme-toggle data-tooltip="Ganti tema" aria-label="Ganti tema"><i class="bi bi-moon-stars"></i></button>

                <div style="position:relative;">
                    <div class="topbar__user" data-dropdown-trigger="menuUser" role="button" tabindex="0">
                        <div class="avatar">{{ auth()->user()->initials() }}</div>
                        <div class="topbar__user-meta">
                            <div class="name">{{ auth()->user()->name }}</div>
                            <div class="role">{{ auth()->user()->role?->name ?? 'Tanpa peran' }}</div>
                        </div>
                        <i class="bi bi-chevron-down text-muted" style="font-size:0.7rem;"></i>
                    </div>
                    <div class="menu-custom" id="menuUser" style="position:absolute; right:0; top:calc(100% + 8px); width:210px;">
                        @if (auth()->user()->hasPermission('setting.view'))
                            <a href="{{ route('settings.edit') }}" class="menu-item"><i class="bi bi-gear"></i> Pengaturan</a>
                        @endif
                        @if (auth()->user()->hasPermission('activity.view'))
                            <a href="{{ route('activity.index') }}" class="menu-item"><i class="bi bi-clock-history"></i> Log Aktivitas</a>
                        @endif
                        <div class="menu-divider"></div>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="menu-item text-danger-item" style="width:100%; text-align:left; background:none; border:0;">
                                <i class="bi bi-box-arrow-right"></i> Keluar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="page-content" id="main-content">
            @include('partials.flash')

            @yield('content')
        </main>
    </div>
</div>

<div class="modal-overlay" id="modalConfirm">
    <div class="modal-custom" style="max-width:420px;">
        <div class="modal-custom__header">
            <h3 style="margin:0;">Konfirmasi</h3>
            <button type="button" class="icon-btn" data-modal-close aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="modal-custom__body">
            <p id="modalConfirmMessage" style="margin:0;"></p>
        </div>
        <div class="modal-custom__footer">
            <button type="button" class="btn-custom btn-outline-custom" id="modalConfirmCancel">Batal</button>
            <button type="button" class="btn-custom btn-primary-custom" id="modalConfirmOk">Ya, lanjutkan</button>
        </div>
    </div>
</div>

@stack('scripts')
</body>
</html>
