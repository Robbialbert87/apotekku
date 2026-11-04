@extends('layouts.app')

@use('App\Support\Format')

@section('title', 'Rencana Pembayaran')

@section('content')
    <div class="page-header">
        <div>
            <ol class="breadcrumb-custom">
                <li><a href="{{ route('dashboard') }}">Beranda</a></li>
                <li class="sep">/</li>
                <li class="current">Rencana Pembayaran</li>
            </ol>
            <h1 class="page-header__title">Rencana Pembayaran</h1>
            <p class="page-header__desc">Pantau invoice yang harus dibayar berdasarkan rentang jatuh tempo atau seluruh sisa tagihan.</p>
        </div>
        <div class="page-header__actions">
            @if (auth()->user()->hasPermission('report.export'))
                <a href="{{ route('reports.export', ['type' => 'outstanding'] + request()->only(['supplier_id', 'payment_status', 'due_status', 'date_from', 'date_to'])) }}" class="btn-custom btn-outline-custom">
                    <i class="bi bi-download"></i> Ekspor CSV
                </a>
            @endif
        </div>
    </div>

    <div class="card-custom">
        <div class="card-custom__header" style="flex-wrap:wrap; gap:12px;">
            <div class="tabs-custom" style="border-bottom:none;">
                <a href="{{ route('plans.index', ['mode' => 'a']) }}" class="tab-btn {{ $mode === 'a' ? 'is-active' : '' }}">Berdasarkan Jatuh Tempo</a>
                <a href="{{ route('plans.index', ['mode' => 'b']) }}" class="tab-btn {{ $mode === 'b' ? 'is-active' : '' }}">Semua Sisa Tagihan</a>
            </div>
        </div>

        <div class="card-custom__body" style="padding-top:0;">
            <form action="{{ route('plans.index') }}" method="GET" class="filter-bar">
                <input type="hidden" name="mode" value="{{ $mode }}">

                <div class="filter-field">
                    <label class="form-label-custom" for="supplier_id">Supplier</label>
                    <select name="supplier_id" id="supplier_id" class="form-select-custom form-control-custom">
                        <option value="">Semua supplier</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected((string) ($filters['supplier_id'] ?? '') === (string) $supplier->id)>{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-field">
                    <label class="form-label-custom" for="date_from">{{ $mode === 'a' ? 'Jatuh Tempo Dari' : 'Dari Tanggal' }}</label>
                    <input type="text" id="date_from" name="date_from" class="form-control-custom date-picker" value="{{ $filters['date_from'] ?? '' }}" placeholder="dd/mm/yyyy">
                </div>

                <div class="filter-field">
                    <label class="form-label-custom" for="date_to">{{ $mode === 'a' ? 'Jatuh Tempo Sampai' : 'Sampai Tanggal' }}</label>
                    <input type="text" id="date_to" name="date_to" class="form-control-custom date-picker" value="{{ $filters['date_to'] ?? '' }}" placeholder="dd/mm/yyyy">
                </div>

                @if ($mode === 'b')
                    <div class="filter-field">
                        <label class="form-label-custom" for="payment_status">Status Bayar</label>
                        <select name="payment_status" id="payment_status" class="form-select-custom form-control-custom">
                            <option value="">Semua</option>
                            @foreach (\App\Enums\InvoicePaymentStatus::options() as $value => $label)
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
                @endif

                <div class="filter-actions">
                    <button type="submit" class="btn-custom btn-primary-custom"><i class="bi bi-funnel"></i> Terapkan</button>
                    <a href="{{ route('plans.index', ['mode' => $mode]) }}" class="btn-custom btn-outline-custom">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="stat-grid stat-grid--3" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));">
        <div class="stat-card">
            <div class="stat-card__top"><div class="stat-card__icon" style="background:var(--color-primary-soft); color:var(--color-primary);"><i class="bi bi-receipt"></i></div></div>
            <div class="stat-card__value">{{ number_format($summary['count'], 0, ',', '.') }}</div>
            <div class="stat-card__label">Invoice Belum Lunas</div>
        </div>
        <div class="stat-card">
            <div class="stat-card__top"><div class="stat-card__icon" style="background:var(--color-warning-soft); color:var(--color-warning);"><i class="bi bi-wallet2"></i></div></div>
            <div class="stat-card__value">{{ Format::rupiah($summary['total']) }}</div>
            <div class="stat-card__label">Total Sisa Tagihan</div>
        </div>
    </div>

    <div class="card-custom">
        <div class="card-custom__header">
            <h3>{{ $mode === 'a' ? 'Tagihan Berdasarkan Rentang Jatuh Tempo' : 'Seluruh Sisa Tagihan' }}</h3>
        </div>
        <div class="table-wrap table-responsive-cards" style="border:none; border-radius:0;">
            <table class="table-custom">
                <thead>
                    <tr><th>Invoice</th><th>Supplier</th><th>Jatuh Tempo</th><th>Status Jatuh Tempo</th><th class="text-end">Total</th><th class="text-end">Sisa</th><th>Status Bayar</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($invoices as $invoice)
                        <tr>
                            <td data-label="Invoice">
                                <a href="{{ route('invoices.show', $invoice) }}"><strong>{{ $invoice->invoice_number }}</strong></a>
                                <div class="cell-sub">{{ Format::tanggal($invoice->invoice_date) }}</div>
                            </td>
                            <td data-label="Supplier">{{ $invoice->supplier->name }}</td>
                            <td data-label="Jatuh Tempo">{{ Format::tanggal($invoice->due_date) }}</td>
                            <td data-label="Status Jatuh Tempo"><x-status-badge :status="$invoice->due_status" /></td>
                            <td data-label="Total" class="text-end money">{{ Format::rupiah($invoice->total_amount) }}</td>
                            <td data-label="Sisa" class="text-end money"><strong>{{ Format::rupiah($invoice->remaining_amount) }}</strong></td>
                            <td data-label="Status Bayar"><x-status-badge :status="$invoice->payment_status" /></td>
                            <td data-label="">
                                <div class="table-row-actions">
                                    @if (auth()->user()->hasPermission('payment.create'))
                                        <a href="{{ route('payments.create', ['supplier_id' => $invoice->supplier_id, 'invoice_id' => $invoice->id]) }}" class="btn-custom btn-success-custom btn-sm-custom">
                                            <i class="bi bi-cash"></i> Bayar
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><x-empty-state icon="bi-calendar-check" title="Tidak ada tagihan pada filter ini" description="Coba ubah rentang tanggal atau filter supplier." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="padding:0 var(--space-5) var(--space-5);">
            {{ $invoices->links() }}
        </div>
    </div>
@endsection
