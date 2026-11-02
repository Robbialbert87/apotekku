@extends('layouts.app')

@use('App\Support\Format')

@section('title', 'Pengguna')

@section('content')
    <div class="page-header">
        <div>
            <ol class="breadcrumb-custom">
                <li><a href="{{ route('dashboard') }}">Beranda</a></li>
                <li class="sep">/</li>
                <li class="current">Pengguna</li>
            </ol>
            <h1 class="page-header__title">Pengguna</h1>
            <p class="page-header__desc">Kelola akun admin dan karyawan beserta perannya.</p>
        </div>
        <div class="page-header__actions">
            @if (auth()->user()->hasPermission('role.view'))
                <a href="{{ route('roles.index') }}" class="btn-custom btn-outline-custom"><i class="bi bi-shield-lock"></i> Peran &amp; Hak Akses</a>
            @endif
            @if (auth()->user()->hasPermission('user.create'))
                <a href="{{ route('users.create') }}" class="btn-custom btn-primary-custom"><i class="bi bi-person-plus"></i> Pengguna Baru</a>
            @endif
        </div>
    </div>

    <div class="card-custom">
        <div class="card-custom__header" style="flex-wrap:wrap; gap:12px;">
            <form action="{{ route('users.index') }}" method="GET" class="filter-bar w-100">
                <div class="filter-field filter-field--grow">
                    <label class="form-label-custom" for="search">Cari</label>
                    <div class="input-group-custom">
                        <i class="bi bi-search"></i>
                        <input type="search" id="search" name="search" class="form-control-custom" value="{{ $filters['search'] ?? '' }}" placeholder="Nama atau email...">
                    </div>
                </div>
                <div class="filter-field">
                    <label class="form-label-custom" for="role_id">Peran</label>
                    <select name="role_id" id="role_id" class="form-select-custom form-control-custom">
                        <option value="">Semua peran</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" @selected((string) ($filters['role_id'] ?? '') === (string) $role->id)>{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-field">
                    <label class="form-label-custom" for="status">Status</label>
                    <select name="status" id="status" class="form-select-custom form-control-custom">
                        <option value="">Semua</option>
                        <option value="active" @selected(($filters['status'] ?? '') === 'active')>Aktif</option>
                        <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Nonaktif</option>
                    </select>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn-custom btn-primary-custom"><i class="bi bi-funnel"></i> Terapkan</button>
                    <a href="{{ route('users.index') }}" class="btn-custom btn-outline-custom">Reset</a>
                </div>
            </form>
        </div>

        <div class="table-wrap table-responsive-cards" style="border:none; border-radius:0;">
            <table class="table-custom datatable" id="table-users">
                <thead><tr><th>Nama</th><th>Email</th><th>Peran</th><th>Status</th><th>Terakhir Masuk</th><th></th></tr></thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td data-label="Nama">
                                <div class="cluster gap-2">
                                    <div class="avatar" style="width:32px;height:32px;font-size:11px;">{{ $user->initials() }}</div>
                                    <div>
                                        <strong>{{ $user->name }}</strong>
                                        @if ($user->is(auth()->user()))
                                            <div class="cell-sub">Akun Anda</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td data-label="Email">{{ $user->email }}</td>
                            <td data-label="Peran">{{ $user->role?->name ?? 'Tanpa peran' }}</td>
                            <td data-label="Status"><span class="badge-custom {{ $user->is_active ? 'badge-success' : 'badge-neutral' }}">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                            <td data-label="Terakhir Masuk">{{ $user->last_login_at ? Format::tanggalWaktu($user->last_login_at) : '-' }}</td>
                            <td data-label="">
                                <div class="table-row-actions">
                                    @if (auth()->user()->hasPermission('user.update'))
                                        <a href="{{ route('users.edit', $user) }}" class="btn-custom btn-ghost-custom btn-sm-custom" title="Ubah"><i class="bi bi-pencil"></i></a>
                                    @endif
                                    @if (auth()->user()->hasPermission('user.update') && ! $user->is(auth()->user()) && ! $user->role?->isAdmin())
                                        <form action="{{ route('users.destroy', $user) }}" method="POST" data-confirm="Hapus pengguna ini?" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-custom btn-ghost-custom btn-sm-custom text-danger" title="Hapus"><i class="bi bi-trash"></i></button>
                                        </form>
                                    @endif
                                    @if (auth()->user()->hasPermission('user.deactivate') && ! $user->is(auth()->user()))
                                        <form action="{{ route('users.toggle-status', $user) }}" method="POST" data-confirm="Ubah status pengguna ini?">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn-custom btn-ghost-custom btn-sm-custom" title="{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                                <i class="bi {{ $user->is_active ? 'bi-pause-circle' : 'bi-play-circle' }}"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty-state icon="bi-people" title="Pengguna tidak ditemukan" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
@endsection
