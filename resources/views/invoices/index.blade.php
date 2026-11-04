@extends('layouts.app')

@use('App\Support\Format')

@section('title', 'Invoice')

@section('content')
    <div class="page-header">
        <div>
            <ol class="breadcrumb-custom">
                <li><a href="{{ route('dashboard') }}">Beranda</a></li>
                <li class="sep">/</li>
                <li class="current">Invoice</li>
            </ol>
            <h1 class="page-header__title">Invoice</h1>
            <p class="page-header__desc">Daftar tagihan supplier beserta status pembayaran dan jatuh tempo.</p>
        </div>
        <div class="page-header__actions">
            @if (auth()->user()->hasPermission('invoice.create'))
                <button type="button" class="btn-custom btn-primary-custom" data-modal-open="modalInvoiceCreate">
                    <i class="bi bi-plus-lg"></i> Invoice Baru
                </button>
            @endif
        </div>
    </div>

    <div class="card-custom">
        <div class="card-custom__header" style="flex-wrap:wrap; gap:12px;">
            <form action="{{ route('invoices.index') }}" method="GET" class="filter-bar w-100">
                <div class="filter-field filter-field--grow">
                    <label class="form-label-custom" for="search">Cari</label>
                    <div class="input-group-custom">
                        <i class="bi bi-search"></i>
                        <input type="search" id="invoice-search" name="search" class="form-control-custom" value="{{ $filters['search'] ?? '' }}" placeholder="Nomor invoice atau supplier..." data-dt-search>
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
                    <label class="form-label-custom" for="due_status">Jatuh Tempo</label>
                    <select name="due_status" id="due_status" class="form-select-custom form-control-custom">
                        <option value="">Semua</option>
                        @foreach (\App\Enums\DueStatus::options() as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['due_status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-field">
                    <label class="form-label-custom" for="date_from">Dari Tanggal</label>
                    <input type="text" id="date_from" name="date_from" class="form-control-custom date-picker" value="{{ $filters['date_from'] ?? '' }}" placeholder="dd/mm/yyyy">
                </div>
                <div class="filter-field">
                    <label class="form-label-custom" for="date_to">Sampai Tanggal</label>
                    <input type="text" id="date_to" name="date_to" class="form-control-custom date-picker" value="{{ $filters['date_to'] ?? '' }}" placeholder="dd/mm/yyyy">
                </div>
                <div class="filter-field">
                    <label class="form-label-custom">Tampilkan</label>
                    <label class="form-check-custom" style="height:38px;">
                        <input type="checkbox" name="include_voided" value="1" @checked($filters['include_voided'] ?? false)>
                        <span class="text-small">Invoice dibatalkan</span>
                    </label>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn-custom btn-primary-custom"><i class="bi bi-funnel"></i> Terapkan</button>
                    <a href="{{ route('invoices.index') }}" class="btn-custom btn-outline-custom">Reset</a>
                </div>
            </form>
        </div>

        <div class="table-wrap table-responsive-cards" style="border:none; border-radius:0;">
            <table class="table-custom datatable" id="table-invoices">
                <thead>
                    <tr>
                        <th>Invoice</th><th>Supplier</th><th>Jatuh Tempo</th><th class="text-end">Total</th>
                        <th class="text-end">Dibayar</th><th class="text-end">Sisa</th><th>Status</th><th>Jatuh Tempo</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoices as $invoice)
                        <tr>
                            <td data-label="Invoice">
                                <a href="{{ route('invoices.show', $invoice) }}"><strong>{{ $invoice->invoice_number }}</strong></a>
                                <div class="cell-sub">{{ Format::tanggal($invoice->invoice_date) }}</div>
                                @if ($invoice->isVoided())
                                    <span class="badge-custom badge-neutral">Dibatalkan</span>
                                @endif
                            </td>
                            <td data-label="Supplier">{{ $invoice->supplier->name }}</td>
                            <td data-label="Jatuh Tempo">{{ Format::tanggal($invoice->due_date) }}</td>
                            <td data-label="Total" class="text-end money">{{ Format::rupiah($invoice->total_amount) }}</td>
                            <td data-label="Dibayar" class="text-end money">{{ Format::rupiah($invoice->paid_amount) }}</td>
                            <td data-label="Sisa" class="text-end money">{{ Format::rupiah($invoice->remaining_amount) }}</td>
                            <td data-label="Status"><x-status-badge :status="$invoice->payment_status" /></td>
                            <td data-label="Jatuh Tempo">
    @if ($invoice->isPaid())
        <span class="text-muted">-</span>
    @else
        <x-status-badge :status="$invoice->due_status" />
    @endif
</td>
                            <td data-label="">
                                <div class="table-row-actions">
                                    <a href="{{ route('invoices.show', $invoice) }}" class="btn-custom btn-ghost-custom btn-sm-custom" title="Detail"><i class="bi bi-eye"></i></a>
                                    @if (auth()->user()->hasPermission('invoice.update'))
                                        <form action="{{ route('invoices.destroy', $invoice) }}" method="POST" data-confirm="Hapus invoice ini?" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-custom btn-ghost-custom btn-sm-custom text-danger" title="Hapus"><i class="bi bi-trash"></i></button>
                                        </form>
                                    @endif
                                    @if (! $invoice->isVoided() && auth()->user()->hasPermission('payment.create') && ! $invoice->isPaid())
                                        <a href="{{ route('payments.create', ['supplier_id' => $invoice->supplier_id, 'invoice_id' => $invoice->id]) }}" class="btn-custom btn-ghost-custom btn-sm-custom" title="Catat pembayaran"><i class="bi bi-cash"></i></a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9"><x-empty-state icon="bi-receipt" title="Invoice tidak ditemukan" description="Ubah filter atau tambahkan invoice baru." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>

    @if (auth()->user()->hasPermission('invoice.create'))
        <div class="modal-overlay" id="modalInvoiceCreate">
            <div class="modal-custom" style="max-width:min(920px, 95vw); width:100%;">
                <div class="modal-custom__header">
                    <h3 style="margin:0;">Invoice Baru</h3>
                    <button type="button" class="icon-btn" data-modal-close aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="modal-custom__body" style="padding: var(--space-4);">
                    @php
                        $invoice = new \App\Models\Invoice();
                        $items = collect();
                        $files = collect();
                    @endphp
                    <form action="{{ route('invoices.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @include('invoices._form', ['modal' => true])
                    </form>
                </div>
            </div>
        </div>

        @if ($errors->any())
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    var modal = document.getElementById('modalInvoiceCreate');
                    if (modal) modal.classList.add('is-open');
                });
            </script>
        @endif
    @endif
@endsection
