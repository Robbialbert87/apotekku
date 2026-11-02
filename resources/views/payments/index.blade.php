@extends('layouts.app')

@use('App\Support\Format')

@section('title', 'Pembayaran')

@section('content')
    <div class="page-header">
        <div>
            <ol class="breadcrumb-custom">
                <li><a href="{{ route('dashboard') }}">Beranda</a></li>
                <li class="sep">/</li>
                <li class="current">Pembayaran</li>
            </ol>
            <h1 class="page-header__title">Pembayaran</h1>
            <p class="page-header__desc">Catat pembayaran ke supplier dan alokasikan ke satu atau beberapa invoice.</p>
        </div>
        <div class="page-header__actions">
            @if (auth()->user()->hasPermission('payment.create'))
                <button type="button" class="btn-custom btn-primary-custom" data-modal-open="modalPaymentCreate">
                    <i class="bi bi-plus-lg"></i> Pembayaran Baru
                </button>
            @endif
        </div>
    </div>

    <div class="card-custom">
        <div class="card-custom__header" style="flex-wrap:wrap; gap:12px;">
            <form action="{{ route('payments.index') }}" method="GET" class="filter-bar w-100">
                <div class="filter-field filter-field--grow">
                    <label class="form-label-custom" for="search">Cari</label>
                    <div class="input-group-custom">
                        <i class="bi bi-search"></i>
                        <input type="search" id="search" name="search" class="form-control-custom" value="{{ $filters['search'] ?? '' }}" placeholder="No. transaksi, referensi, atau supplier...">
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
                    <label class="form-label-custom" for="status">Status</label>
                    <select name="status" id="status" class="form-select-custom form-control-custom">
                        <option value="">Semua</option>
                        <option value="posted" @selected(($filters['status'] ?? '') === 'posted')>Tercatat</option>
                        <option value="cancelled" @selected(($filters['status'] ?? '') === 'cancelled')>Dibatalkan</option>
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
                <div class="filter-actions">
                    <button type="submit" class="btn-custom btn-primary-custom"><i class="bi bi-funnel"></i> Terapkan</button>
                    <a href="{{ route('payments.index') }}" class="btn-custom btn-outline-custom">Reset</a>
                </div>
            </form>
        </div>

        <div class="table-wrap table-responsive-cards" style="border:none; border-radius:0;">
            <table class="table-custom datatable" id="table-payments">
                <thead>
                    <tr><th>No. Transaksi</th><th>Tanggal</th><th>Supplier</th><th>Metode</th><th class="text-end">Nominal</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            <td data-label="No. Transaksi">
                                <a href="{{ route('payments.show', $payment) }}"><strong>{{ $payment->payment_number }}</strong></a>
                                @if ($payment->reference_number)
                                    <div class="cell-sub">Ref: {{ $payment->reference_number }}</div>
                                @endif
                            </td>
                            <td data-label="Tanggal">{{ Format::tanggal($payment->payment_date) }}</td>
                            <td data-label="Supplier">{{ $payment->supplier->name }}</td>
                            <td data-label="Metode">{{ $payment->method->label() }}</td>
                            <td data-label="Nominal" class="text-end money">{{ Format::rupiah($payment->total_amount) }}</td>
                            <td data-label="Status">
                                <span class="badge-custom {{ $payment->isCancelled() ? 'badge-neutral' : 'badge-success' }}">{{ $payment->status->label() }}</span>
                            </td>
                            <td data-label="">
                                <div class="table-row-actions">
                                    <a href="{{ route('payments.show', $payment) }}" class="btn-custom btn-ghost-custom btn-sm-custom" title="Detail"><i class="bi bi-eye"></i></a>
                                    @if (auth()->user()->hasPermission('payment.create'))
                                        <form action="{{ route('payments.destroy', $payment) }}" method="POST" data-confirm="Hapus pembayaran ini?" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-custom btn-ghost-custom btn-sm-custom text-danger" title="Hapus"><i class="bi bi-trash"></i></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-empty-state icon="bi-cash-stack" title="Pembayaran tidak ditemukan" description="Ubah filter atau catat pembayaran baru." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>

    @if (auth()->user()->hasPermission('payment.create'))
        @php
            $paymentSupplierId = request()->integer('supplier_id') ?: null;
            $paymentInvoiceId = request()->integer('invoice_id') ?: null;
            $paymentSupplier = $paymentSupplierId ? \App\Models\Supplier::query()->find($paymentSupplierId) : null;
            $paymentInvoices = $paymentSupplier === null
                ? collect()
                : \App\Models\Invoice::query()->outstanding()->withPaidAmount()->where('supplier_id', $paymentSupplier->id)->orderBy('due_date')->get();
        @endphp
        <div class="modal-overlay" id="modalPaymentCreate">
            <div class="modal-custom" style="max-width:min(1180px, 96vw); width:100%;">
                <div class="modal-custom__header">
                    <h3 style="margin:0;">Pembayaran Baru</h3>
                    <button type="button" class="icon-btn" data-modal-close aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="modal-custom__body">
                    <form action="{{ route('payments.store') }}" method="POST" enctype="multipart/form-data" data-payment-form>
                        @csrf
                        @include('payments._form', [
                            'suppliers' => $suppliers,
                            'supplier' => $paymentSupplier,
                            'invoices' => $paymentInvoices,
                            'invoiceId' => $paymentInvoiceId,
                            'methods' => \App\Enums\PaymentMethod::options(),
                        ])
                    </form>
                </div>
            </div>
        </div>

        @if (request()->boolean('payment_modal') || $errors->any())
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    var modal = document.getElementById('modalPaymentCreate');
                    if (modal) modal.classList.add('is-open');
                });
            </script>
        @endif
    @endif
@endsection
