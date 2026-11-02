@extends('layouts.app')

@section('title', 'Atur Hak Akses')

@section('content')
    @php
        $selected = old('permissions', $role->permissions->pluck('id')->all());
        $selected = array_map('intval', (array) $selected);
    @endphp

    <div class="page-header">
        <div>
            <ol class="breadcrumb-custom">
                <li><a href="{{ route('dashboard') }}">Beranda</a></li>
                <li class="sep">/</li>
                <li><a href="{{ route('users.index') }}">Pengguna</a></li>
                <li class="sep">/</li>
                <li><a href="{{ route('roles.index') }}">Peran</a></li>
                <li class="sep">/</li>
                <li class="current">{{ $role->name }}</li>
            </ol>
            <h1 class="page-header__title">Hak Akses {{ $role->name }}</h1>
            <p class="page-header__desc">{{ $role->description ?: 'Centang izin yang boleh digunakan oleh peran ini.' }}</p>
        </div>
        <div class="page-header__actions">
            <a href="{{ route('roles.index') }}" class="btn-custom btn-outline-custom"><i class="bi bi-arrow-left"></i> Kembali</a>
        </div>
    </div>

    <form action="{{ route('roles.update', $role) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="card-custom">
            <div class="card-custom__header" style="flex-wrap:wrap; gap:12px;">
                <h3>Daftar Izin</h3>
                <div class="cluster gap-2">
                    <button type="button" class="btn-custom btn-ghost-custom btn-sm-custom" data-permission-toggle="all">Pilih semua</button>
                    <button type="button" class="btn-custom btn-ghost-custom btn-sm-custom" data-permission-toggle="none">Kosongkan</button>
                </div>
            </div>

            <div class="card-custom__body">
                @error('permissions')<div class="alert-custom alert-danger-custom" style="margin-bottom:var(--space-4);"><i class="bi bi-exclamation-triangle-fill"></i><div>{{ $message }}</div></div>@enderror

                @foreach ($permissions as $group => $items)
                    <div class="form-section" style="margin-bottom:var(--space-5);">
                        <div class="cluster" style="justify-content:space-between; margin-bottom:var(--space-3);">
                            <h4 class="form-section__title" style="margin:0; font-size:var(--fs-small); text-transform:uppercase; letter-spacing:0.04em; color:var(--color-text-muted);">{{ $group }}</h4>
                            <button type="button" class="btn-custom btn-ghost-custom btn-sm-custom" data-permission-toggle="group" data-permission-scope="{{ $loop->index }}">Pilih grup</button>
                        </div>

                        <div class="form-grid" data-permission-group="{{ $loop->index }}">
                            @foreach ($items as $permission)
                                <div class="col-6">
                                    <label class="form-check-custom" style="align-items:flex-start;">
                                        <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked(in_array($permission->id, $selected, true)) data-permission-checkbox>
                                        <span>
                                            <strong style="display:block;">{{ $permission->name }}</strong>
                                            <span class="text-muted text-small">{{ $permission->slug }}</span>
                                        </span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="form-actions">
            <a href="{{ route('roles.index') }}" class="btn-custom btn-outline-custom">Batal</a>
            <button type="submit" class="btn-custom btn-primary-custom"><i class="bi bi-check-lg"></i> Simpan Hak Akses</button>
        </div>
    </form>
@endsection
