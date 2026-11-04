@extends('layouts.app')

@use('App\Support\Format')

@section('title', 'Detail Invoice')

@section('content')
    <div class="page-header">
        <div>
            <ol class="breadcrumb-custom">
                <li><a href="{{ route('dashboard') }}">Beranda</a></li>
                <li class="sep">/</li>
                <li><a href="{{ route('invoices.index') }}">Invoice</a></li>
                <li class="sep">/</li>
                <li class="current">{{ $invoice->invoice_number }}</li>
            </ol>
            <h1 class="page-header__title">{{ $invoice->invoice_number }}</h1>
            <p class="page-header__desc">
                {{ $invoice->supplier->name }} ·
                <x-status-badge :status="$invoice->payment_status" />
                <x-status-badge :status="$invoice->due_status" />
                @if ($invoice->isVoided())<span class="badge-custom badge-neutral">Dibatalkan</span>@endif
            </p>
        </div>
        <div class="page-header__actions">
            @if (! $invoice->isVoided() && ! $invoice->isPaid() && auth()->user()->hasPermission('payment.create'))
                <a href="{{ route('payments.create', ['supplier_id' => $invoice->supplier_id, 'invoice_id' => $invoice->id]) }}" class="btn-custom btn-success-custom">
                    <i class="bi bi-cash-stack"></i> Catat Pembayaran
                </a>
            @endif
            @if (! $invoice->isVoided() && auth()->user()->hasPermission('invoice.update'))
                <a href="{{ route('invoices.edit', $invoice) }}" class="btn-custom btn-outline-custom"><i class="bi bi-pencil"></i> Ubah</a>
            @endif
            <a href="{{ route('invoices.index') }}" class="btn-custom btn-outline-custom"><i class="bi bi-arrow-left"></i> Kembali</a>
        </div>
    </div>

    @if ($invoice->isVoided())
        <div class="alert-custom alert-danger-custom" role="alert">
            <i class="bi bi-x-circle-fill"></i>
            <div>
                <div class="alert-title">Invoice dibatalkan</div>
                {{ $invoice->void_reason }} — {{ $invoice->voider?->name ?? 'Sistem' }}, {{ Format::tanggalWaktu($invoice->voided_at) }}
            </div>
        </div>
    @endif

    <div class="content-row">
        <div class="card-custom">
            <div class="card-custom__header"><h3>Informasi Invoice</h3></div>
            <div class="card-custom__body">
                <div class="detail-grid detail-grid--3">
                    <div class="detail-item">
                        <div class="detail-item__label">Supplier</div>
                        <div class="detail-item__value"><a href="{{ route('suppliers.show', $invoice->supplier) }}">{{ $invoice->supplier->name }}</a></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-item__label">Tanggal Invoice</div>
                        <div class="detail-item__value">{{ Format::tanggal($invoice->invoice_date) }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-item__label">Tanggal Diterima</div>
                        <div class="detail-item__value">{{ Format::tanggal($invoice->received_date) }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-item__label">Jatuh Tempo</div>
                        <div class="detail-item__value">{{ Format::tanggal($invoice->due_date) }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-item__label">Diinput Oleh</div>
                        <div class="detail-item__value">{{ $invoice->creator?->name ?? '-' }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-item__label">Terakhir Diubah</div>
                        <div class="detail-item__value">{{ Format::tanggalWaktu($invoice->updated_at) }}</div>
                    </div>
                </div>

                @if ($invoice->notes)
                    <div style="margin-top:var(--space-5);">
                        <div class="detail-item__label">Catatan</div>
                        <p style="margin:0;">{{ $invoice->notes }}</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="card-custom">
            <div class="card-custom__header"><h3>Ringkasan</h3></div>
            <div class="card-custom__body">
                <div class="summary-box">
                    <div class="summary-row"><span>Total Tagihan</span><span class="money">{{ Format::rupiah($invoice->total_amount) }}</span></div>
                    <div class="summary-row"><span>Sudah Dibayar</span><span class="money" style="color:var(--color-success);">{{ Format::rupiah($invoice->paid_amount) }}</span></div>
                    <div class="summary-row summary-row--total"><span>Sisa Tagihan</span><span class="money" style="color:var(--color-danger);">{{ Format::rupiah($invoice->remaining_amount) }}</span></div>
                </div>

                <div style="margin-top:var(--space-4);">
                    <div class="detail-item__label">Status Pembayaran</div>
                    <x-status-badge :status="$invoice->payment_status" />
                </div>
                <div style="margin-top:var(--space-3);">
                    <div class="detail-item__label">Status Jatuh Tempo</div>
                    @if ($invoice->isPaid())
                        <span class="text-muted">-</span>
                    @else
                        <x-status-badge :status="$invoice->due_status" />
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card-custom">
        <div class="card-custom__header"><h3>Rincian Obat</h3></div>
        <div class="table-wrap table-responsive-cards" style="border:none; border-radius:0;">
            <table class="table-custom">
                <thead><tr><th>Nama Obat</th><th class="text-end">Jumlah</th></tr></thead>
                <tbody>
                    @forelse ($invoice->items as $item)
                        <tr>
                            <td data-label="Nama Obat">{{ $item->name }}</td>
                            <td data-label="Jumlah" class="text-end">{{ $item->quantityLabel() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2"><x-empty-state icon="bi-capsule" title="Tidak ada rincian obat" description="Rincian obat bersifat opsional." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card-custom">
        <div class="card-custom__header"><h3>Lampiran</h3></div>
        <div class="card-custom__body">
            @if ($invoice->files->isEmpty())
                <p class="text-muted text-small" style="margin:0;">Tidak ada lampiran.</p>
            @else
                <ul class="file-list">
                    @foreach ($invoice->files as $file)
                        <li>
                            <div class="file-icon"><i class="bi {{ $file->isImage() ? 'bi-image' : 'bi-file-earmark-pdf' }}"></i></div>
                            <div class="file-meta">
                                <div class="file-name">{{ $file->original_name }}</div>
                                <div class="file-size">{{ Format::fileSize($file->size) }} · {{ $file->uploader?->name ?? '-' }}</div>
                            </div>
                            <a href="{{ route('invoices.files.show', [$invoice, $file]) }}" target="_blank" class="btn-custom btn-ghost-custom btn-sm-custom" title="Lihat"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('invoices.files.show', [$invoice, $file, 'download' => 1]) }}" class="btn-custom btn-ghost-custom btn-sm-custom" title="Unduh"><i class="bi bi-download"></i></a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <div class="card-custom">
        <div class="card-custom__header"><h3>Riwayat Pembayaran</h3></div>
        <div class="table-wrap table-responsive-cards" style="border:none; border-radius:0;">
            <table class="table-custom">
                <thead><tr><th>No. Transaksi</th><th>Tanggal</th><th>Metode</th><th class="text-end">Nominal</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($invoice->allocations->sortByDesc(fn ($allocation) => $allocation->payment->payment_date) as $allocation)
                        <tr>
                            <td data-label="No. Transaksi">
                                <a href="{{ route('payments.show', $allocation->payment) }}"><strong>{{ $allocation->payment->payment_number }}</strong></a>
                            </td>
                            <td data-label="Tanggal">{{ Format::tanggal($allocation->payment->payment_date) }}</td>
                            <td data-label="Metode">{{ $allocation->payment->method->label() }}</td>
                            <td data-label="Nominal" class="text-end money">{{ Format::rupiah($allocation->amount) }}</td>
                            <td data-label="Status">
                                <span class="badge-custom {{ $allocation->payment->isCancelled() ? 'badge-neutral' : 'badge-success' }}">
                                    {{ $allocation->payment->status->label() }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-empty-state icon="bi-cash-stack" title="Belum ada pembayaran" /></td></tr>
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

        @if (! $invoice->isVoided() && auth()->user()->hasPermission('invoice.void'))
            <div class="card-custom">
                <div class="card-custom__header"><h3>Batalkan Invoice</h3></div>
                <div class="card-custom__body">
                    <p class="text-muted text-small">Invoice yang sudah memiliki pembayaran tidak dapat dibatalkan. Batalkan pembayaran terlebih dahulu.</p>
                    <form action="{{ route('invoices.void', $invoice) }}" method="POST" data-confirm="Batalkan invoice ini? Tindakan ini tercatat di log aktivitas.">
                        @csrf
                        <label class="form-label-custom" for="void_reason">Alasan Pembatalan <span class="text-danger">*</span></label>
                        <textarea id="void_reason" name="void_reason" rows="3" class="form-control-custom" required maxlength="500" placeholder="Contoh: salah input supplier.">{{ old('void_reason') }}</textarea>
                        <div class="form-actions" style="justify-content:flex-start;">
                            <button type="submit" class="btn-custom btn-danger-custom"><i class="bi bi-x-circle"></i> Batalkan Invoice</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
@endsection
