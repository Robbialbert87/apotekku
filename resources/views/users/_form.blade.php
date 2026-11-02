@php
    $isEdit = $user->exists;
@endphp

<div class="form-grid">
    <div class="col-6">
        <label class="form-label-custom" for="name">Nama <span class="text-danger">*</span></label>
        <input type="text" id="name" name="name" class="form-control-custom @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required maxlength="100">
        @error('name')<div class="form-error-text">{{ $message }}</div>@enderror
    </div>

    <div class="col-6">
        <label class="form-label-custom" for="email">Email <span class="text-danger">*</span></label>
        <input type="email" id="email" name="email" class="form-control-custom @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required maxlength="150">
        @error('email')<div class="form-error-text">{{ $message }}</div>@enderror
    </div>

    <div class="col-6">
        <label class="form-label-custom" for="role_id">Peran <span class="text-danger">*</span></label>
        <select id="role_id" name="role_id" class="form-select-custom form-control-custom @error('role_id') is-invalid @enderror" required>
            <option value="">Pilih peran</option>
            @foreach ($roles as $role)
                <option value="{{ $role->id }}" @selected((string) old('role_id', $user->role_id) === (string) $role->id)>{{ $role->name }}</option>
            @endforeach
        </select>
        @error('role_id')<div class="form-error-text">{{ $message }}</div>@enderror
    </div>

    <div class="col-6">
        <label class="form-label-custom" for="password">Kata Sandi {{ $isEdit ? '' : '*' }}</label>
        <input type="password" id="password" name="password" class="form-control-custom @error('password') is-invalid @enderror" @required(! $isEdit) autocomplete="new-password" minlength="8">
        <div class="form-hint">{{ $isEdit ? 'Biarkan kosong bila tidak ingin mengubah kata sandi.' : 'Minimal 8 karakter.' }}</div>
        @error('password')<div class="form-error-text">{{ $message }}</div>@enderror
    </div>

    <div class="col-6">
        <label class="form-label-custom" for="password_confirmation">Konfirmasi Kata Sandi {{ $isEdit ? '' : '*' }}</label>
        <input type="password" id="password_confirmation" name="password_confirmation" class="form-control-custom" @required(! $isEdit) autocomplete="new-password" minlength="8">
    </div>

    <div class="col-12">
        <label class="form-check-custom">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active ?? true)) @disabled($isEdit && $user->is(auth()->user()))>
            <span>Akun aktif dan dapat masuk ke aplikasi</span>
        </label>
        @if ($isEdit && $user->is(auth()->user()))
            <div class="form-hint">Anda tidak dapat menonaktifkan akun sendiri.</div>
        @endif
    </div>
</div>

<div class="form-actions">
    <a href="{{ route('users.index') }}" class="btn-custom btn-outline-custom">Batal</a>
    <button type="submit" class="btn-custom btn-primary-custom"><i class="bi bi-check-lg"></i> {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Pengguna' }}</button>
</div>
