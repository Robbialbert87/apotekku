@extends('layouts.app')

@use('App\Support\Format')

@section('title', 'Dashboard')

@section('content')
    <div class="page-header">
        <div>
            <ol class="breadcrumb-custom">
                <li><a href="{{ route('dashboard') }}">Beranda</a></li>
                <li class="sep">/</li>
                <li class="current">Dashboard</li>
            </ol>
            <h1 class="page-header__title">Dashboard</h1>
            <p class="page-header__desc">Ringkasan tagihan supplier dan pembayaran apotek.</p>
        </div>
        <div class="page-header__actions">
            <a href="{{ route('plans.index') }}" class="btn-custom btn-outline-custom"><i class="bi bi-calendar2-check"></i> Rencana Pembayaran</a>
            @if (auth()->user()->hasPermission('invoice.create'))
                <a href="{{ route('invoices.create') }}" class="btn-custom btn-primary-custom"><i class="bi bi-plus-lg"></i> Invoice Baru</a>
            @endif
        </div>
    </div>

    <div class="stat-grid">
        @foreach ($metrics as $metric)
            @php
                $tone = $metric['tone'];
                $bg = "var(--color-{$tone}-soft)";
                $color = "var(--color-{$tone})";
            @endphp
            <div class="stat-card">
                <div class="stat-card__top">
                    <div class="stat-card__icon" style="background:{{ $bg }}; color:{{ $color }};"><i class="bi {{ $metric['icon'] }}"></i></div>
                </div>
                <div class="stat-card__value">
                    {{ $metric['format'] === 'currency' ? Format::rupiah($metric['value']) : number_format((int) $metric['value'], 0, ',', '.') }}
                </div>
                <div class="stat-card__label">{{ $metric['label'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="chart-row">
        <div class="card-custom">
            <div class="card-custom__header">
                <h3>Tagihan vs Pembayaran</h3>
                <span class="text-muted text-small">6 bulan terakhir</span>
            </div>
            <div class="card-custom__body">
                <div class="chart-canvas-wrap"><canvas id="chartMonthly" aria-label="Grafik tagihan dan pembayaran" role="img"></canvas></div>
                <div class="legend-row">
                    <span class="legend-item"><span class="legend-dot" style="background:var(--color-primary);"></span>Tagihan</span>
                    <span class="legend-item"><span class="legend-dot" style="background:var(--color-success);"></span>Pembayaran</span>
                </div>
            </div>
        </div>

        <div class="card-custom">
            <div class="card-custom__header">
                <h3>Status Pembayaran</h3>
            </div>
            <div class="card-custom__body">
                <div class="chart-canvas-wrap"><canvas id="chartStatus" aria-label="Grafik status pembayaran" role="img"></canvas></div>
                <div class="legend-row">
                    @foreach ($statusChart['labels'] as $index => $label)
                        <span class="legend-item">
                            <span class="legend-dot" style="background:{{ $statusChart['colors'][$index] }};"></span>
                            {{ $label }} ({{ $statusChart['data'][$index] }})
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="content-row">
        <div class="card-custom">
            <div class="card-custom__header">
                <h3>Invoice Terbaru</h3>
                <a href="{{ route('invoices.index') }}" class="text-small">Lihat semua</a>
            </div>
            <div class="table-wrap table-responsive-cards" style="border:none; border-radius:0;">
                <table class="table-custom">
                    <thead>
                        <tr><th>Invoice</th><th>Supplier</th><th>Jatuh Tempo</th><th>Total</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($latestInvoices as $invoice)
                            <tr>
                                <td data-label="Invoice">
                                    <a href="{{ route('invoices.show', $invoice) }}"><strong>{{ $invoice->invoice_number }}</strong></a>
                                    <div class="cell-sub">{{ Format::tanggal($invoice->invoice_date) }}</div>
                                </td>
                                <td data-label="Supplier">{{ $invoice->supplier->name }}</td>
                                <td data-label="Jatuh Tempo">{{ Format::tanggal($invoice->due_date) }}</td>
                                <td data-label="Total" class="money">{{ Format::rupiah($invoice->total_amount) }}</td>
                                <td data-label="Status"><x-status-badge :status="$invoice->payment_status" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><x-empty-state icon="bi-receipt" title="Belum ada invoice" description="Invoice supplier akan tampil di sini." /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="stack gap-4">
            <div class="card-custom">
                <div class="card-custom__header">
                    <h3>Jatuh Tempo Terdekat</h3>
                    <a href="{{ route('plans.index') }}" class="text-small">Lihat</a>
                </div>
                <div class="card-custom__body stack gap-3">
                    @forelse ($nearestDue as $invoice)
                        <div class="person-card">
                            <div class="avatar"><i class="bi bi-receipt"></i></div>
                            <div class="person-card__meta">
                                <div class="person-card__name">{{ $invoice->invoice_number }}</div>
                                <div class="person-card__sub">{{ $invoice->supplier->name }} · {{ Format::tanggal($invoice->due_date) }}</div>
                            </div>
                            <div class="text-end">
                                <div class="text-small money">{{ Format::rupiah($invoice->remaining_amount) }}</div>
                                <x-status-badge :status="$invoice->due_status" />
                            </div>
                        </div>
                    @empty
                        <x-empty-state icon="bi-calendar-check" title="Tidak ada jatuh tempo terdekat" />
                    @endforelse
                </div>
            </div>

            <div class="card-custom">
                <div class="card-custom__header">
                    <h3>Invoice Terlambat</h3>
                    <a href="{{ route('plans.index', ['mode' => 'b', 'due_status' => 'overdue']) }}" class="text-small">Lihat</a>
                </div>
                <div class="card-custom__body stack gap-3">
                    @forelse ($overdueInvoices as $invoice)
                        <div class="person-card">
                            <div class="avatar" style="background:var(--color-danger-soft); color:var(--color-danger);"><i class="bi bi-exclamation-triangle"></i></div>
                            <div class="person-card__meta">
                                <div class="person-card__name">{{ $invoice->invoice_number }}</div>
                                <div class="person-card__sub">{{ $invoice->supplier->name }} · {{ Format::tanggal($invoice->due_date) }}</div>
                            </div>
                            <div class="text-small money">{{ Format::rupiah($invoice->remaining_amount) }}</div>
                        </div>
                    @empty
                        <x-empty-state icon="bi-emoji-smile" title="Tidak ada invoice terlambat" />
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="card-custom">
        <div class="card-custom__header">
            <h3>Supplier dengan Sisa Tagihan Terbesar</h3>
            <a href="{{ route('suppliers.index') }}" class="text-small">Lihat supplier</a>
        </div>
        <div class="card-custom__body">
            @forelse ($topSuppliers as $supplier)
                @php
                    $max = max(1, (int) $topSuppliers->max(fn ($row) => \App\Support\Money::toCents($row['outstanding'])));
                    $width = min(100, round(\App\Support\Money::toCents($supplier['outstanding']) / $max * 100));
                @endphp
                <div style="margin-bottom:var(--space-4);">
                    <div class="cluster" style="justify-content:space-between; margin-bottom:6px;">
                        <strong class="text-small">{{ $supplier['name'] }}</strong>
                        <span class="text-small money">{{ Format::rupiah($supplier['outstanding']) }}</span>
                    </div>
                    <div class="progress-custom"><div class="progress-custom__bar" style="width:{{ $width }}%;"></div></div>
                </div>
            @empty
                <x-empty-state icon="bi-truck" title="Belum ada sisa tagihan supplier" />
            @endforelse
        </div>
    </div>

    <script type="application/json" id="dashboard-data">
        {!! json_encode([
            'monthly' => $monthlyChart,
            'status' => $statusChart,
        ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
    </script>
@endsection
