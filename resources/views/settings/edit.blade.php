@extends('layouts.app')

@section('title', 'Pengaturan')

@section('content')
    <div class="page-header">
        <div>
            <ol class="breadcrumb-custom">
                <li><a href="{{ route('dashboard') }}">Beranda</a></li>
                <li class="sep">/</li>
                <li class="current">Pengaturan</li>
            </ol>
            <h1 class="page-header__title">Pengaturan</h1>
            <p class="page-header__desc">Informasi apotek, preferensi default sistem, dan keamanan akun Anda.</p>
        </div>
        <div class="page-header__actions">
            @if (auth()->user()->hasPermission('activity.view'))
                <a href="{{ route('activity.index') }}" class="btn-custom btn-outline-custom"><i class="bi bi-clock-history"></i> Log Aktivitas</a>
            @endif
        </div>
    </div>

    <div class="content-row">
        <!-- Kolom Kiri: Identitas Apotek & Preferensi Sistem -->
        <div class="stack gap-4">
            <form action="{{ route('settings.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="card-custom">
                    <div class="card-custom__header">
                        <div>
                            <h3 style="margin:0;"><i class="bi bi-building"></i> Identitas Apotek</h3>
                            <p class="text-muted text-small" style="margin:2px 0 0 0;">Informasi resmi yang ditampilkan pada header invoice dan laporan.</p>
                        </div>
                    </div>
                    <div class="card-custom__body">
                        <div class="form-grid">
                            <div class="col-8">
                                <label class="form-label-custom" for="apotek_name">Nama Apotek <span class="text-danger">*</span></label>
                                <input type="text" id="apotek_name" name="apotek_name"
                                       class="form-control-custom @error('apotek_name') is-invalid @enderror"
                                       value="{{ old('apotek_name', $settings['apotek_name']) }}" required maxlength="150" placeholder="Contoh: Apotek Farma Sehat">
                                @error('apotek_name')<div class="form-error-text">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-4">
                                <label class="form-label-custom" for="apotek_phone">Telepon</label>
                                <input type="text" id="apotek_phone" name="apotek_phone"
                                       class="form-control-custom @error('apotek_phone') is-invalid @enderror"
                                       value="{{ old('apotek_phone', $settings['apotek_phone']) }}" maxlength="30" placeholder="08xxxxxxxxxx">
                                @error('apotek_phone')<div class="form-error-text">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-4">
                                <label class="form-label-custom" for="apotek_email">Email</label>
                                <input type="email" id="apotek_email" name="apotek_email"
                                       class="form-control-custom @error('apotek_email') is-invalid @enderror"
                                       value="{{ old('apotek_email', $settings['apotek_email']) }}" maxlength="150" placeholder="info@apotek.com">
                                @error('apotek_email')<div class="form-error-text">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-8">
                                <label class="form-label-custom" for="apotek_address">Alamat Lengkap</label>
                                <textarea id="apotek_address" name="apotek_address" rows="2"
                                          class="form-control-custom @error('apotek_address') is-invalid @enderror"
                                          maxlength="1000" placeholder="Alamat lengkap lokasi apotek...">{{ old('apotek_address', $settings['apotek_address']) }}</textarea>
                                @error('apotek_address')<div class="form-error-text">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-custom" style="margin-top:var(--space-4);">
                    <div class="card-custom__header">
                        <div>
                            <h3 style="margin:0;"><i class="bi bi-sliders"></i> Preferensi Operasional</h3>
                            <p class="text-muted text-small" style="margin:2px 0 0 0;">Nilai default transaksi invoice dan cetakan dokumen.</p>
                        </div>
                    </div>
                    <div class="card-custom__body">
                        <div class="form-grid">
                            <div class="col-6">
                                <label class="form-label-custom" for="invoice_due_days">Jatuh Tempo Default (hari) <span class="text-danger">*</span></label>
                                <input type="number" id="invoice_due_days" name="invoice_due_days" min="1" max="365"
                                       class="form-control-custom @error('invoice_due_days') is-invalid @enderror"
                                       value="{{ old('invoice_due_days', $settings['invoice_due_days']) }}" required>
                                <div class="form-hint">Saran tanggal jatuh tempo otomatis saat input invoice baru.</div>
                                @error('invoice_due_days')<div class="form-error-text">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-6">
                                <label class="form-label-custom" for="report_footer">Catatan Kaki Laporan</label>
                                <input type="text" id="report_footer" name="report_footer"
                                       class="form-control-custom @error('report_footer') is-invalid @enderror"
                                       value="{{ old('report_footer', $settings['report_footer']) }}" maxlength="255" placeholder="Teks catatan kaki pada dokumen cetak">
                                <div class="form-hint">Teks ringkas yang tertera di kaki cetakan laporan.</div>
                                @error('report_footer')<div class="form-error-text">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        @if (auth()->user()->hasPermission('setting.update'))
                            <div class="form-actions" style="margin-top:var(--space-5); padding-top:var(--space-4); border-top:1px solid var(--color-border);">
                                <a href="{{ route('dashboard') }}" class="btn-custom btn-outline-custom">Batal</a>
                                <button type="submit" class="btn-custom btn-primary-custom">
                                    <i class="bi bi-check-lg"></i> Simpan Pengaturan
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <!-- Kolom Kanan: Keamanan Akun & Info Sistem -->
        <div class="stack gap-4">
            <!-- Form Ganti Kata Sandi -->
            <div class="card-custom">
                <div class="card-custom__header">
                    <div>
                        <h3 style="margin:0;"><i class="bi bi-shield-lock"></i> Keamanan Akun</h3>
                        <p class="text-muted text-small" style="margin:2px 0 0 0;">Perbarui kata sandi login akun Anda.</p>
                    </div>
                </div>
                <div class="card-custom__body">
                    <form action="{{ route('settings.password') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="form-grid">
                            <div class="col-12">
                                <label class="form-label-custom" for="current_password">Kata Sandi Saat Ini <span class="text-danger">*</span></label>
                                <input type="password" id="current_password" name="current_password"
                                       class="form-control-custom @error('current_password') is-invalid @enderror"
                                       required autocomplete="current-password" placeholder="••••••••">
                                @error('current_password')<div class="form-error-text">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label-custom" for="password">Kata Sandi Baru <span class="text-danger">*</span></label>
                                <input type="password" id="password" name="password"
                                       class="form-control-custom @error('password') is-invalid @enderror"
                                       required autocomplete="new-password" minlength="8" placeholder="Minimal 8 karakter">
                                <div class="form-hint">Minimal 8 karakter.</div>
                                @error('password')<div class="form-error-text">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label-custom" for="password_confirmation">Konfirmasi Kata Sandi Baru <span class="text-danger">*</span></label>
                                <input type="password" id="password_confirmation" name="password_confirmation"
                                       class="form-control-custom"
                                       required autocomplete="new-password" minlength="8" placeholder="Ulangi kata sandi baru">
                            </div>
                        </div>

                        <div class="form-actions" style="margin-top:var(--space-4);">
                            <button type="submit" class="btn-custom btn-primary-custom w-100" style="justify-content:center;">
                                <i class="bi bi-key-fill"></i> Ganti Kata Sandi
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Ringkasan Info Sistem -->
            <div class="card-custom">
                <div class="card-custom__header">
                    <div>
                        <h3 style="margin:0;"><i class="bi bi-info-circle"></i> Info Akun & Sistem</h3>
                        <p class="text-muted text-small" style="margin:2px 0 0 0;">Detail sesi pengguna aktif.</p>
                    </div>
                </div>
                <div class="card-custom__body">
                    <div class="summary-box">
                        <div class="summary-row">
                            <span>Pengguna</span>
                            <strong>{{ auth()->user()->name }}</strong>
                        </div>
                        <div class="summary-row">
                            <span>Email</span>
                            <span class="text-muted">{{ auth()->user()->email }}</span>
                        </div>
                        <div class="summary-row">
                            <span>Hak Akses</span>
                            <span class="badge-custom badge-info">{{ auth()->user()->role?->name ?? 'User' }}</span>
                        </div>
                        <div class="summary-row">
                            <span>Zona Waktu</span>
                            <span>{{ config('app.timezone') }}</span>
                        </div>
                        <div class="summary-row">
                            <span>Bahasa</span>
                            <span>Indonesia ({{ config('app.locale') }})</span>
                        </div>
                        <div class="summary-row">
                            <span>Mata Uang</span>
                            <span>Rupiah (IDR)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
