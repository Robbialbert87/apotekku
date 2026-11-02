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
            <p class="page-header__desc">Informasi apotek dan preferensi default aplikasi.</p>
        </div>
        <div class="page-header__actions">
            @if (auth()->user()->hasPermission('activity.view'))
                <a href="{{ route('activity.index') }}" class="btn-custom btn-outline-custom"><i class="bi bi-clock-history"></i> Log Aktivitas</a>
            @endif
        </div>
    </div>

    <form action="{{ route('settings.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="content-row">
            <div class="card-custom">
                <div class="card-custom__header"><h3>Identitas Apotek</h3></div>
                <div class="card-custom__body">
                    <div class="form-grid">
                        <div class="col-8">
                            <label class="form-label-custom" for="apotek_name">Nama Apotek <span class="text-danger">*</span></label>
                            <input type="text" id="apotek_name" name="apotek_name"
                                   class="form-control-custom @error('apotek_name') is-invalid @enderror"
                                   value="{{ old('apotek_name', $settings['apotek_name']) }}" required maxlength="150">
                            @error('apotek_name')<div class="form-error-text">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-4">
                            <label class="form-label-custom" for="apotek_phone">Telepon</label>
                            <input type="text" id="apotek_phone" name="apotek_phone"
                                   class="form-control-custom @error('apotek_phone') is-invalid @enderror"
                                   value="{{ old('apotek_phone', $settings['apotek_phone']) }}" maxlength="30">
                            @error('apotek_phone')<div class="form-error-text">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-4">
                            <label class="form-label-custom" for="apotek_email">Email</label>
                            <input type="email" id="apotek_email" name="apotek_email"
                                   class="form-control-custom @error('apotek_email') is-invalid @enderror"
                                   value="{{ old('apotek_email', $settings['apotek_email']) }}" maxlength="150">
                            @error('apotek_email')<div class="form-error-text">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-8">
                            <label class="form-label-custom" for="apotek_address">Alamat</label>
                            <textarea id="apotek_address" name="apotek_address" rows="2"
                                      class="form-control-custom @error('apotek_address') is-invalid @enderror"
                                      maxlength="1000">{{ old('apotek_address', $settings['apotek_address']) }}</textarea>
                            @error('apotek_address')<div class="form-error-text">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-custom">
                <div class="card-custom__header"><h3>Preferensi</h3></div>
                <div class="card-custom__body">
                    <div class="form-grid">
                        <div class="col-12">
                            <label class="form-label-custom" for="invoice_due_days">Jatuh Tempo Default (hari) <span class="text-danger">*</span></label>
                            <input type="number" id="invoice_due_days" name="invoice_due_days" min="1" max="365"
                                   class="form-control-custom @error('invoice_due_days') is-invalid @enderror"
                                   value="{{ old('invoice_due_days', $settings['invoice_due_days']) }}" required>
                            <div class="form-hint">Dipakai sebagai saran tanggal jatuh tempo saat membuat invoice baru.</div>
                            @error('invoice_due_days')<div class="form-error-text">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label-custom" for="report_footer">Catatan Kaki Laporan</label>
                            <input type="text" id="report_footer" name="report_footer"
                                   class="form-control-custom @error('report_footer') is-invalid @enderror"
                                   value="{{ old('report_footer', $settings['report_footer']) }}" maxlength="255">
                            @error('report_footer')<div class="form-error-text">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="summary-box" style="margin-top:var(--space-5);">
                        <div class="summary-row"><span>Zona Waktu</span><span>{{ config('app.timezone') }}</span></div>
                        <div class="summary-row"><span>Bahasa</span><span>{{ config('app.locale') }}</span></div>
                        <div class="summary-row"><span>Mata Uang</span><span>Rupiah (IDR)</span></div>
                    </div>
                </div>
            </div>
        </div>

        @if (auth()->user()->hasPermission('setting.update'))
            <div class="form-actions">
                <a href="{{ route('dashboard') }}" class="btn-custom btn-outline-custom">Batal</a>
                <button type="submit" class="btn-custom btn-primary-custom"><i class="bi bi-check-lg"></i> Simpan Pengaturan</button>
            </div>
        @endif
    </form>
@endsection
