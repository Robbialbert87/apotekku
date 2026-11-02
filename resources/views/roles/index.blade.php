@extends('layouts.app')

@section('title', 'Peran & Hak Akses')

@section('content')
    <div class="page-header">
        <div>
            <ol class="breadcrumb-custom">
                <li><a href="{{ route('dashboard') }}">Beranda</a></li>
                <li class="sep">/</li>
                <li><a href="{{ route('users.index') }}">Pengguna</a></li>
                <li class="sep">/</li>
                <li class="current">Peran</li>
            </ol>
            <h1 class="page-header__title">Peran &amp; Hak Akses</h1>
            <p class="page-header__desc">Atur izin untuk setiap peran. Peran Admin selalu memiliki akses penuh.</p>
        </div>
        <div class="page-header__actions">
            <a href="{{ route('users.index') }}" class="btn-custom btn-outline-custom"><i class="bi bi-people"></i> Daftar Pengguna</a>
        </div>
    </div>

    <div class="stat-grid stat-grid--3" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));">
        @foreach ($roles as $role)
            <div class="stat-card">
                <div class="stat-card__top">
                    <div class="stat-card__icon" style="background:{{ $role->isAdmin() ? 'var(--color-primary-soft)' : 'var(--color-info-soft)' }}; color:{{ $role->isAdmin() ? 'var(--color-primary)' : 'var(--color-info)' }};">
                        <i class="bi {{ $role->isAdmin() ? 'bi-shield-lock-fill' : 'bi-person-badge' }}"></i>
                    </div>
                    @if ($role->is_system)
                        <span class="badge-custom badge-neutral">Sistem</span>
                    @endif
                </div>
                <div class="stat-card__value" style="font-size:var(--fs-h4);">{{ $role->name }}</div>
                <div class="stat-card__label">
                    {{ $role->users_count }} pengguna · {{ $role->isAdmin() ? 'semua izin' : $role->permissions->count().' izin' }}
                </div>
                <div style="margin-top:var(--space-3);">
                    @if ($role->isAdmin())
                        <span class="text-muted text-small">Akses penuh otomatis</span>
                    @elseif (auth()->user()->hasPermission('role.update'))
                        <a href="{{ route('roles.edit', $role) }}" class="btn-custom btn-outline-custom btn-sm-custom">
                            <i class="bi bi-sliders"></i> Atur Izin
                        </a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="card-custom">
        <div class="card-custom__header">
            <h3>Ringkasan Hak Akses</h3>
        </div>
        <div class="table-wrap table-responsive-cards" style="border:none; border-radius:0;">
            <table class="table-custom">
                <thead>
                    <tr><th>Peran</th><th>Deskripsi</th><th>Pengguna</th><th>Izin</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach ($roles as $role)
                        <tr>
                            <td data-label="Peran"><strong>{{ $role->name }}</strong><div class="cell-sub">{{ $role->slug }}</div></td>
                            <td data-label="Deskripsi">{{ $role->description ?: '-' }}</td>
                            <td data-label="Pengguna">{{ $role->users_count }}</td>
                            <td data-label="Izin">
                                @if ($role->isAdmin())
                                    <span class="badge-custom badge-success">Akses penuh</span>
                                @else
                                    <span class="badge-custom badge-info">{{ $role->permissions->count() }} izin</span>
                                @endif
                            </td>
                            <td data-label="">
                                <div class="table-row-actions">
                                    @if (! $role->isAdmin() && auth()->user()->hasPermission('role.update'))
                                        <a href="{{ route('roles.edit', $role) }}" class="btn-custom btn-ghost-custom btn-sm-custom" title="Atur izin"><i class="bi bi-pencil"></i></a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
