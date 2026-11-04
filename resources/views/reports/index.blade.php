@extends('layouts.app')

@use('App\Support\Format')

@section('title', 'Laporan')

@section('content')
    <div class="page-header">
        <div>
            <ol class="breadcrumb-custom">
                <li><a href="{{ route('dashboard') }}">Beranda</a></li>
                <li class="sep">/</li>
                <li class="current">Laporan</li>
            </ol>
            <h1 class="page-header__title">Laporan</h1>
            <p class="page-header__desc">Lima jenis laporan keuangan supplier dengan filter dan ekspor CSV.</p>
        </div>
        <div class="page-header__actions">
            @if (auth()->user()->hasPermission('report.export'))
                <a href="{{ route('reports.export', ['type' => $type] + array_filter($filters, fn ($value) => $value !== null && $value !== '')) }}" class="btn-custom btn-primary-custom">
                    <i class="bi bi-download"></i> Ekspor CSV
                </a>
            @endif
        </div>
    </div>

    <div class="card-custom">
        <div class="card-custom__header" style="padding-bottom:0;">
            <div class="tabs-custom" style="border-bottom:none; flex-wrap:wrap;">
                @foreach ($types as $value => $label)
                    <a href="{{ route('reports.index', ['type' => $value]) }}" class="tab-btn {{ $type === $value ? 'is-active' : '' }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>

        <div class="card-custom__body">
            <form action="{{ route('reports.index') }}" method="GET" class="filter-bar">
                <input type="hidden" name="type" value="{{ $type }}">

                <div class="filter-field filter-field--grow">
                    <label class="form-label-custom" for="search">Cari</label>
                    <div class="input-group-custom">
                        <i class="bi bi-search"></i>
                        <input type="search" id="search" name="search" class="form-control-custom" value="{{ $filters['search'] ?? '' }}" placeholder="Nomor invoice, supplier, atau transaksi...">
                    </div>
                </div>

                <div class="filter-field">
                    <label class="form-label-custom" for="supplier_id">Supplier</label>
                    <select name="supplier_id" id="supplier_id" class="form-select-custom form-control-custom">
                        <option value="">Semua supplier</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected((string) ($filters['supplier_id'] ?? '') === (string) $supplier->id)>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>

                @if ($type !== 'payments')
                    <div class="filter-field">
                        <label class="form-label-custom" for="payment_status">Status Bayar</label>
                        <select name="payment_status" id="payment_status" class="form-select-custom form-control-custom">
                            <option value="">Semua</option>
                            @foreach ($paymentStatuses as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['payment_status'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-field">
                        <label class="form-label-custom" for="due_status">Status Jatuh Tempo</label>
                        <select name="due_status" id="due_status" class="form-select-custom form-control-custom">
                            <option value="">Semua</option>
                            @foreach ($dueStatuses as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['due_status'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <div class="filter-field">
                        <label class="form-label-custom" for="record_status">Status Transaksi</label>
                        <select name="record_status" id="record_status" class="form-select-custom form-control-custom">
                            <option value="posted" @selected(($filters['record_status'] ?? 'posted') === 'posted')>Tercatat</option>
                            <option value="cancelled" @selected(($filters['record_status'] ?? '') === 'cancelled')>Dibatalkan</option>
                        </select>
                    </div>
                @endif

                <div class="filter-field">
                    <label class="form-label-custom" for="date_from">Dari Tanggal</label>
                    <input type="text" id="date_from" name="date_from" class="form-control-custom date-picker" value="{{ $filters['date_from'] ?? '' }}" placeholder="dd/mm/yyyy">
                </div>
                <div class="filter-field">
                    <label class="form-label-custom" for="date_to">Sampai Tanggal</label>
                    <input type="text" id="date_to" name="date_to" class="form-control-custom date-picker" value="{{ $filters['date_to'] ?? '' }}" placeholder="dd/mm/yyyy">
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn-custom btn-primary-custom"><i class="bi bi-funnel"></i> Terapkan</button>
                    <a href="{{ route('reports.index', ['type' => $type]) }}" class="btn-custom btn-outline-custom">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="stat-grid stat-grid--3" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));">
        <div class="stat-card">
            <div class="stat-card__top"><div class="stat-card__icon" style="background:var(--color-primary-soft); color:var(--color-primary);"><i class="bi bi-list-check"></i></div></div>
            <div class="stat-card__value">{{ number_format($summary['count'], 0, ',', '.') }}</div>
            <div class="stat-card__label">Jumlah Data</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__top"><div class="stat-card__icon" style="background:var(--color-warning-soft); color:var(--color-warning);"><i class="bi bi-cash-stack"></i></div></div>
            <div class="stat-card__value">{{ Format::rupiah($summary['value']) }}</div>
            <div class="stat-card__label">{{ $summary['label'] }}</div>
        </div>
    </div>

    <div class="card-custom">
        <div class="card-custom__header"><h3>{{ $types[$type] }}</h3></div>

        <div class="table-wrap table-responsive-cards" style="border:none; border-radius:0;">
            <table class="table-custom">
                @if ($type === 'payments')
                    <thead><tr><th>No. Transaksi</th><th>Tanggal</th><th>Supplier</th><th>Metode</th><th>Referensi</th><th class="text-end">Nominal</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($records as $payment)
                            <tr>
                                <td data-label="No. Transaksi"><a href="{{ route('payments.show', $payment) }}"><strong>{{ $payment->payment_number }}</strong></a></td>
                                <td data-label="Tanggal">{{ Format::tanggal($payment->payment_date) }}</td>
                                <td data-label="Supplier">{{ $payment->supplier->name }}</td>
                                <td data-label="Metode">{{ $payment->method->label() }}</td>
                                <td data-label="Referensi">{{ $payment->reference_number ?: '-' }}</td>
                                <td data-label="Nominal" class="text-end money">{{ Format::rupiah($payment->total_amount) }}</td>
                                <td data-label="Status"><span class="badge-custom {{ $payment->isCancelled() ? 'badge-neutral' : 'badge-success' }}">{{ $payment->status->label() }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="7"><x-empty-state icon="bi-cash-stack" title="Tidak ada pembayaran pada filter ini" /></td></tr>
                        @endforelse
                    </tbody>
                @elseif ($type === 'suppliers')
                    <thead><tr><th>Supplier</th><th>Kontak</th><th>Invoice</th><th class="text-end">Total Tagihan</th><th class="text-end">Dibayar</th><th class="text-end">Sisa</th></tr></thead>
                    <tbody>
                        @forelse ($records as $supplier)
                            <tr>
                                <td data-label="Supplier"><a href="{{ route('suppliers.show', $supplier) }}"><strong>{{ $supplier->name }}</strong></a></td>
                                <td data-label="Kontak">{{ $supplier->contact_name ?: '-' }}<div class="cell-sub">{{ $supplier->phone }}</div></td>
                                <td data-label="Invoice">{{ $supplier->invoices_count }}</td>
                                <td data-label="Total Tagihan" class="text-end money">{{ Format::rupiah($supplier->billed_total ?? 0) }}</td>
                                <td data-label="Dibayar" class="text-end money">{{ Format::rupiah($supplier->paid_total ?? 0) }}</td>
                                <td data-label="Sisa" class="text-end money">{{ Format::rupiah($supplier->outstanding_total ?? 0) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><x-empty-state icon="bi-truck" title="Tidak ada supplier pada filter ini" /></td></tr>
                        @endforelse
                    </tbody>
                @elseif ($type === 'due')
                    <thead><tr><th>Invoice</th><th>Supplier</th><th>Tanggal Invoice</th><th>Jatuh Tempo</th><th class="text-end">Sisa</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($records as $invoice)
                            <tr>
                                <td data-label="Invoice"><a href="{{ route('invoices.show', $invoice) }}"><strong>{{ $invoice->invoice_number }}</strong></a></td>
                                <td data-label="Supplier">{{ $invoice->supplier->name }}</td>
                                <td data-label="Tanggal Invoice">{{ Format::tanggal($invoice->invoice_date) }}</td>
                                <td data-label="Jatuh Tempo">{{ Format::tanggal($invoice->due_date) }}</td>
                                <td data-label="Sisa" class="text-end money">{{ Format::rupiah($invoice->remaining_amount) }}</td>
                                <td data-label="Status"><x-status-badge :status="$invoice->due_status" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><x-empty-state icon="bi-calendar-check" title="Tidak ada jatuh tempo pada filter ini" /></td></tr>
                        @endforelse
                    </tbody>
                @elseif ($type === 'outstanding')
                    <thead><tr><th>Invoice</th><th>Supplier</th><th>Jatuh Tempo</th><th class="text-end">Total</th><th class="text-end">Dibayar</th><th class="text-end">Sisa</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($records as $invoice)
                            <tr>
                                <td data-label="Invoice"><a href="{{ route('invoices.show', $invoice) }}"><strong>{{ $invoice->invoice_number }}</strong></a></td>
                                <td data-label="Supplier">{{ $invoice->supplier->name }}</td>
                                <td data-label="Jatuh Tempo">{{ Format::tanggal($invoice->due_date) }}</td>
                                <td data-label="Total" class="text-end money">{{ Format::rupiah($invoice->total_amount) }}</td>
                                <td data-label="Dibayar" class="text-end money">{{ Format::rupiah($invoice->paid_amount) }}</td>
                                <td data-label="Sisa" class="text-end money">{{ Format::rupiah($invoice->remaining_amount) }}</td>
                                <td data-label="Status"><x-status-badge :status="$invoice->payment_status" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="7"><x-empty-state icon="bi-wallet2" title="Tidak ada sisa tagihan pada filter ini" /></td></tr>
                        @endforelse
                    </tbody>
                @else
                    <thead><tr><th>Invoice</th><th>Supplier</th><th>Tanggal Invoice</th><th>Jatuh Tempo</th><th class="text-end">Total</th><th class="text-end">Sisa</th><th>Status Bayar</th></tr></thead>
                    <tbody>
                        @forelse ($records as $invoice)
                            <tr>
                                <td data-label="Invoice"><a href="{{ route('invoices.show', $invoice) }}"><strong>{{ $invoice->invoice_number }}</strong></a></td>
                                <td data-label="Supplier">{{ $invoice->supplier->name }}</td>
                                <td data-label="Tanggal Invoice">{{ Format::tanggal($invoice->invoice_date) }}</td>
                                <td data-label="Jatuh Tempo">{{ Format::tanggal($invoice->due_date) }}</td>
                                <td data-label="Total" class="text-end money">{{ Format::rupiah($invoice->total_amount) }}</td>
                                <td data-label="Sisa" class="text-end money">{{ Format::rupiah($invoice->remaining_amount) }}</td>
                                <td data-label="Status Bayar"><x-status-badge :status="$invoice->payment_status" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="7"><x-empty-state icon="bi-receipt" title="Tidak ada invoice pada filter ini" /></td></tr>
                        @endforelse
                    </tbody>
                @endif
            </table>
        </div>

        <div style="padding:0 var(--space-5) var(--space-5);">
            {{ $records->links() }}
        </div>
    </div>
@endsection
