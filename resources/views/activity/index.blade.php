@extends('layouts.app')

@use('App\Support\Format')

@section('title', 'Log Aktivitas')

@section('content')
    <div class="page-header">
        <div>
            <ol class="breadcrumb-custom">
                <li><a href="{{ route('dashboard') }}">Beranda</a></li>
                <li class="sep">/</li>
                <li class="current">Log Aktivitas</li>
            </ol>
            <h1 class="page-header__title">Log Aktivitas</h1>
            <p class="page-header__desc">Jejak audit perubahan data penting di aplikasi.</p>
        </div>
        <div class="page-header__actions">
            @if (auth()->user()->hasPermission('setting.view'))
                <a href="{{ route('settings.edit') }}" class="btn-custom btn-outline-custom"><i class="bi bi-gear"></i> Pengaturan</a>
            @endif
        </div>
    </div>

    <div class="card-custom">
        <div class="card-custom__header" style="flex-wrap:wrap; gap:12px;">
            <form action="{{ route('activity.index') }}" method="GET" class="filter-bar w-100">
                <div class="filter-field filter-field--grow">
                    <label class="form-label-custom" for="search">Cari</label>
                    <div class="input-group-custom">
                        <i class="bi bi-search"></i>
                        <input type="search" id="search" name="search" class="form-control-custom"
                               value="{{ $filters['search'] ?? '' }}" placeholder="Aksi, deskripsi, atau nama pengguna...">
                    </div>
                </div>
                <div class="filter-field">
                    <label class="form-label-custom" for="date_from">Dari Tanggal</label>
                    <input type="date" id="date_from" name="date_from" class="form-control-custom" value="{{ $filters['date_from'] ?? '' }}">
                </div>
                <div class="filter-field">
                    <label class="form-label-custom" for="date_to">Sampai Tanggal</label>
                    <input type="date" id="date_to" name="date_to" class="form-control-custom" value="{{ $filters['date_to'] ?? '' }}">
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn-custom btn-primary-custom"><i class="bi bi-funnel"></i> Terapkan</button>
                    <a href="{{ route('activity.index') }}" class="btn-custom btn-outline-custom">Reset</a>
                </div>
            </form>
        </div>

        <div class="table-wrap table-responsive-cards" style="border:none; border-radius:0;">
            <table class="table-custom">
                <thead>
                    <tr><th>Waktu</th><th>Pengguna</th><th>Aksi</th><th>Deskripsi</th><th>Alamat IP</th></tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        @php
                            $tone = match (true) {
                                str_contains($log->action, 'cancelled'), str_contains($log->action, 'voided') => 'badge-danger',
                                str_contains($log->action, 'created') => 'badge-success',
                                str_contains($log->action, 'updated'), str_contains($log->action, 'changed') => 'badge-warning',
                                default => 'badge-info',
                            };
                        @endphp
                        <tr>
                            <td data-label="Waktu">
                                <strong>{{ Format::tanggalWaktu($log->created_at) }}</strong>
                            </td>
                            <td data-label="Pengguna">
                                {{ $log->user?->name ?? 'Sistem' }}
                                @if ($log->user?->role)
                                    <div class="cell-sub">{{ $log->user->role->name }}</div>
                                @endif
                            </td>
                            <td data-label="Aksi">
                                <span class="badge-custom {{ $tone }}">{{ $log->action }}</span>
                            </td>
                            <td data-label="Deskripsi">
                                {{ $log->description }}
                                @if (! empty($log->properties))
                                    <div class="cell-sub">{{ \Illuminate\Support\Str::limit(json_encode($log->properties, JSON_UNESCAPED_UNICODE), 90) }}</div>
                                @endif
                            </td>
                            <td data-label="Alamat IP">{{ $log->ip_address ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-empty-state icon="bi-clock-history" title="Belum ada aktivitas" description="Aktivitas pengguna akan tercatat di sini." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="padding:0 var(--space-5) var(--space-5);">
            {{ $logs->links() }}
        </div>
    </div>
@endsection
