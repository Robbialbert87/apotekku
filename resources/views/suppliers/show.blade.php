@extends('layouts.app')

@use('App\Support\Format')

@section('title', 'Detail Supplier')

@section('content')
    <div class="page-header">
        <div>
            <ol class="breadcrumb-custom">
                <li><a href="{{ route('dashboard') }}">Beranda</a></li>
                <li class="sep">/</li>
                <li><a href="{{ route('suppliers.index') }}">Supplier</a></li>
                <li class="sep">/</li>
                <li class="current">{{ $supplier->name }}</li>
            </ol>
            <h1 class="page-header__title">{{ $supplier->name }}</h1>
            <p class="page-header__desc">
                <span class="badge-custom {{ $supplier->is_active ? 'badge-success' : 'badge-neutral' }}">{{ $supplier->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                <span class="text-muted text-small" style="margin-left:8px;">{{ $supplier->invoices_count }} invoice tercatat</span>
            </p>
        </div>
        <div class="page-header__actions">
            <a href="{{ route('suppliers.index') }}" class="btn-custom btn-outline-custom"><i class="bi bi-arrow-left"></i> Kembali</a>
            @if (auth()->user()->hasPermission('invoice.create'))
                <a href="{{ route('invoices.create', ['supplier_id' => $supplier->id]) }}" class="btn-custom btn-outline-custom"><i class="bi bi-receipt"></i> Invoice Baru</a>
            @endif
            @if (auth()->user()->hasPermission('payment.create'))
                <a href="{{ route('payments.create', ['supplier_id' => $supplier->id]) }}" class="btn-custom btn-outline-custom"><i class="bi bi-cash-stack"></i> Catat Pembayaran</a>
            @endif
            @if (auth()->user()->hasPermission('supplier.update'))
                <a href="{{ route('suppliers.edit', $supplier) }}" class="btn-custom btn-primary-custom"><i class="bi bi-pencil"></i> Ubah</a>
            @endif
        </div>
    </div>

    <div class="content-row">
        <div class="card-custom">
            <div class="card-custom__header"><h3>Informasi Supplier</h3></div>
            <div class="card-custom__body">
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-item__label">Nama Kontak</div>
                        <div class="detail-item__value">{{ $supplier->contact_name ?: '-' }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-item__label">Telepon</div>
                        <div class="detail-item__value">{{ $supplier->phone }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-item__label">Alamat</div>
                        <div class="detail-item__value">{{ $supplier->address }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-item__label">Catatan</div>
                        <div class="detail-item__value">{{ $supplier->notes ?: '-' }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-item__label">Ditambahkan</div>
                        <div class="detail-item__value">{{ Format::tanggalWaktu($supplier->created_at) }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-item__label">Terakhir Diubah</div>
                        <div class="detail-item__value">{{ Format::tanggalWaktu($supplier->updated_at) }}</div>
                    </div>
                </div>

                @if (auth()->user()->hasPermission('supplier.deactivate'))
                    <form action="{{ route('suppliers.toggle-status', $supplier) }}" method="POST" data-confirm="Ubah status supplier ini?" style="margin-top:var(--space-5);">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn-custom {{ $supplier->is_active ? 'btn-outline-custom' : 'btn-success-custom' }}">
                            <i class="bi {{ $supplier->is_active ? 'bi-pause-circle' : 'bi-play-circle' }}"></i>
                            {{ $supplier->is_active ? 'Nonaktifkan Supplier' : 'Aktifkan Supplier' }}
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="card-custom">
            <div class="card-custom__header">
                <h3>Invoice Terakhir</h3>
                <a href="{{ route('invoices.index', ['supplier_id' => $supplier->id]) }}" class="text-small">Lihat semua</a>
            </div>
            <div class="table-wrap table-responsive-cards" style="border:none; border-radius:0;">
                <table class="table-custom">
                    <thead><tr><th>Invoice</th><th>Jatuh Tempo</th><th>Sisa</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($invoices as $invoice)
                            <tr>
                                <td data-label="Invoice">
                                    <a href="{{ route('invoices.show', $invoice) }}"><strong>{{ $invoice->invoice_number }}</strong></a>
                                    <div class="cell-sub">{{ Format::tanggal($invoice->invoice_date) }}</div>
                                </td>
                                <td data-label="Jatuh Tempo">{{ Format::tanggal($invoice->due_date) }}</td>
                                <td data-label="Sisa" class="money">{{ Format::rupiah($invoice->remaining_amount) }}</td>
                                <td data-label="Status"><x-status-badge :status="$invoice->payment_status" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><x-empty-state icon="bi-receipt" title="Belum ada invoice" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card-custom">
        <div class="card-custom__header">
            <h3>Pembayaran Terakhir</h3>
            <a href="{{ route('payments.index', ['supplier_id' => $supplier->id]) }}" class="text-small">Lihat semua</a>
        </div>
        <div class="table-wrap table-responsive-cards" style="border:none; border-radius:0;">
            <table class="table-custom">
                <thead><tr><th>No. Transaksi</th><th>Tanggal</th><th>Metode</th><th>Nominal</th></tr></thead>
                <tbody>
                    @forelse ($recentPayments as $payment)
                        <tr>
                            <td data-label="No. Transaksi"><a href="{{ route('payments.show', $payment) }}"><strong>{{ $payment->payment_number }}</strong></a></td>
                            <td data-label="Tanggal">{{ Format::tanggal($payment->payment_date) }}</td>
                            <td data-label="Metode">{{ $payment->method->label() }}</td>
                            <td data-label="Nominal" class="money">{{ Format::rupiah($payment->total_amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><x-empty-state icon="bi-cash-stack" title="Belum ada pembayaran" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
