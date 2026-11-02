@extends('layouts.app')

@use('App\Support\Format')

@section('title', 'Detail Pembayaran')

@section('content')
    <div class="page-header">
        <div>
            <ol class="breadcrumb-custom">
                <li><a href="{{ route('dashboard') }}">Beranda</a></li>
                <li class="sep">/</li>
                <li><a href="{{ route('payments.index') }}">Pembayaran</a></li>
                <li class="sep">/</li>
                <li class="current">{{ $payment->payment_number }}</li>
            </ol>
            <h1 class="page-header__title">{{ $payment->payment_number }}</h1>
            <p class="page-header__desc">
                {{ $payment->supplier->name }} ·
                <span class="badge-custom {{ $payment->isCancelled() ? 'badge-neutral' : 'badge-success' }}">{{ $payment->status->label() }}</span>
            </p>
        </div>
        <div class="page-header__actions">
            <button type="button" class="btn-custom btn-outline-custom" onclick="window.print()"><i class="bi bi-printer"></i> Cetak</button>
        </div>
    </div>

    @if ($payment->isCancelled())
        <div class="alert-custom alert-danger-custom" role="alert">
            <i class="bi bi-x-circle-fill"></i>
            <div>
                <div class="alert-title">Pembayaran dibatalkan</div>
                {{ $payment->cancel_reason }} — {{ $payment->canceller?->name ?? 'Sistem' }}, {{ Format::tanggalWaktu($payment->cancelled_at) }}
            </div>
        </div>
    @endif

    <div class="content-row">
        <div class="card-custom">
            <div class="card-custom__header"><h3>Informasi Pembayaran</h3></div>
            <div class="card-custom__body">
                <div class="detail-grid detail-grid--3">
                    <div class="detail-item">
                        <div class="detail-item__label">Supplier</div>
                        <div class="detail-item__value"><a href="{{ route('suppliers.show', $payment->supplier) }}">{{ $payment->supplier->name }}</a></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-item__label">Tanggal</div>
                        <div class="detail-item__value">{{ Format::tanggal($payment->payment_date) }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-item__label">Metode</div>
                        <div class="detail-item__value">{{ $payment->method->label() }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-item__label">Nomor Referensi</div>
                        <div class="detail-item__value">{{ $payment->reference_number ?: '-' }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-item__label">Dicatat Oleh</div>
                        <div class="detail-item__value">{{ $payment->creator?->name ?? '-' }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-item__label">Waktu Pencatatan</div>
                        <div class="detail-item__value">{{ Format::tanggalWaktu($payment->created_at) }}</div>
                    </div>
                </div>

                @if ($payment->notes)
                    <div style="margin-top:var(--space-5);">
                        <div class="detail-item__label">Catatan</div>
                        <p style="margin:0;">{{ $payment->notes }}</p>
                    </div>
                @endif

                @if ($payment->proof_path)
                    <div style="margin-top:var(--space-5);">
                        <div class="detail-item__label">Bukti Pembayaran</div>
                        <a href="{{ route('payments.proof', $payment) }}" target="_blank" class="btn-custom btn-outline-custom btn-sm-custom">
                            <i class="bi bi-paperclip"></i> Lihat Bukti
                        </a>
                        <a href="{{ route('payments.proof', [$payment, 'download' => 1]) }}" class="btn-custom btn-ghost-custom btn-sm-custom">
                            <i class="bi bi-download"></i> Unduh
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <div class="card-custom">
            <div class="card-custom__header"><h3>Ringkasan</h3></div>
            <div class="card-custom__body">
                <div class="summary-box">
                    <div class="summary-row summary-row--total"><span>Total Pembayaran</span><span class="money">{{ Format::rupiah($payment->total_amount) }}</span></div>
                    <div class="summary-row"><span>Jumlah Invoice</span><span>{{ $payment->allocations->count() }}</span></div>
                    <div class="summary-row"><span>Total Dialokasikan</span><span class="money">{{ Format::rupiah($payment->allocated_amount) }}</span></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card-custom">
        <div class="card-custom__header"><h3>Alokasi Invoice</h3></div>
        <div class="table-wrap table-responsive-cards" style="border:none; border-radius:0;">
            <table class="table-custom">
                <thead><tr><th>Invoice</th><th>Supplier</th><th>Jatuh Tempo</th><th class="text-end">Nominal</th><th>Status Invoice</th></tr></thead>
                <tbody>
                    @forelse ($payment->allocations as $allocation)
                        <tr>
                            <td data-label="Invoice"><a href="{{ route('invoices.show', $allocation->invoice) }}"><strong>{{ $allocation->invoice->invoice_number }}</strong></a></td>
                            <td data-label="Supplier">{{ $allocation->invoice->supplier->name }}</td>
                            <td data-label="Jatuh Tempo">{{ Format::tanggal($allocation->invoice->due_date) }}</td>
                            <td data-label="Nominal" class="text-end money">{{ Format::rupiah($allocation->amount) }}</td>
                            <td data-label="Status Invoice"><x-status-badge :status="$allocation->invoice->payment_status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-empty-state icon="bi-receipt" title="Tidak ada alokasi" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="content-row">
        <div class="card-custom">
            <div class="card-custom__header"><h3>Log Aktivitas</h3></div>
            <div class="card-custom__body">
                <ul class="timeline-custom">
                    @forelse ($logs as $log)
                        <li class="timeline-item">
                            <span class="timeline-dot"></span>
                            <div>
                                <div class="timeline-item__title">{{ $log->description }}</div>
                                <div class="timeline-item__time">{{ $log->user?->name ?? 'Sistem' }} · {{ Format::tanggalWaktu($log->created_at) }}</div>
                            </div>
                        </li>
                    @empty
                        <li class="text-muted text-small">Belum ada aktivitas.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        @if (! $payment->isCancelled() && auth()->user()->hasPermission('payment.cancel'))
            <div class="card-custom">
                <div class="card-custom__header"><h3>Batalkan Pembayaran</h3></div>
                <div class="card-custom__body">
                    <p class="text-muted text-small">Pembayaran tidak dihapus, tetapi ditandai dibatalkan. Alokasi akan dikeluarkan dari perhitungan sisa tagihan.</p>
                    <form action="{{ route('payments.cancel', $payment) }}" method="POST" data-confirm="Batalkan pembayaran ini? Tindakan ini tercatat di log aktivitas.">
                        @csrf
                        <label class="form-label-custom" for="cancel_reason">Alasan Pembatalan <span class="text-danger">*</span></label>
                        <textarea id="cancel_reason" name="cancel_reason" rows="3" class="form-control-custom" required maxlength="500" placeholder="Contoh: transfer gagal / salah input nominal.">{{ old('cancel_reason') }}</textarea>
                        <div class="form-actions" style="justify-content:flex-start;">
                            <button type="submit" class="btn-custom btn-danger-custom"><i class="bi bi-x-circle"></i> Batalkan Pembayaran</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
@endsection
