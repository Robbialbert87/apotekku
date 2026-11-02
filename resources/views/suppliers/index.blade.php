@extends('layouts.app')

@section('title', 'Supplier')

@section('content')
    <div class="page-header">
        <div>
            <ol class="breadcrumb-custom">
                <li><a href="{{ route('dashboard') }}">Beranda</a></li>
                <li class="sep">/</li>
                <li class="current">Supplier</li>
            </ol>
            <h1 class="page-header__title">Supplier</h1>
            <p class="page-header__desc">Kelola data supplier obat dan kontak yang dapat dihubungi.</p>
        </div>
        <div class="page-header__actions">
            @if (auth()->user()->hasPermission('supplier.create'))
                <button type="button" class="btn-custom btn-primary-custom" data-modal-open="modalSupplierCreate">
                    <i class="bi bi-plus-lg"></i> Supplier Baru
                </button>
            @endif
        </div>
    </div>

    <div class="card-custom">
        <div class="card-custom__header" style="flex-wrap:wrap; gap:12px;">
            <form action="{{ route('suppliers.index') }}" method="GET" class="filter-bar w-100">
                <div class="filter-field filter-field--grow">
                    <label class="form-label-custom" for="search">Cari</label>
                    <div class="input-group-custom">
                        <i class="bi bi-search"></i>
                        <input type="search" id="search" name="search" class="form-control-custom" value="{{ $filters['search'] ?? '' }}" placeholder="Nama supplier, kontak, atau telepon...">
                    </div>
                </div>
                <div class="filter-field">
                    <label class="form-label-custom" for="status">Status</label>
                    <select name="status" id="status" class="form-select-custom form-control-custom">
                        <option value="">Semua status</option>
                        <option value="active" @selected(($filters['status'] ?? '') === 'active')>Aktif</option>
                        <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Nonaktif</option>
                    </select>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn-custom btn-primary-custom"><i class="bi bi-funnel"></i> Terapkan</button>
                    <a href="{{ route('suppliers.index') }}" class="btn-custom btn-outline-custom">Reset</a>
                </div>
            </form>
        </div>

        <div class="table-wrap table-responsive-cards" style="border:none; border-radius:0;">
            <table class="table-custom datatable" id="table-suppliers">
                <thead>
                    <tr><th>Nama</th><th>Kontak</th><th>Telepon</th><th>Invoice</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($suppliers as $supplier)
                        <tr>
                            <td data-label="Nama">
                                <a href="{{ route('suppliers.show', $supplier) }}"><strong>{{ $supplier->name }}</strong></a>
                                <div class="cell-sub">{{ \Illuminate\Support\Str::limit($supplier->address, 55) }}</div>
                            </td>
                            <td data-label="Kontak">{{ $supplier->contact_name ?: '-' }}</td>
                            <td data-label="Telepon">{{ $supplier->phone }}</td>
                            <td data-label="Invoice">{{ $supplier->invoices_count }}</td>
                            <td data-label="Status">
                                <span class="badge-custom {{ $supplier->is_active ? 'badge-success' : 'badge-neutral' }}">
                                    {{ $supplier->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td data-label="">
                                <div class="table-row-actions">
                                    <a href="{{ route('suppliers.show', $supplier) }}" class="btn-custom btn-ghost-custom btn-sm-custom" title="Detail"><i class="bi bi-eye"></i></a>
                                    @if (auth()->user()->hasPermission('supplier.update'))
                                        <a href="{{ route('suppliers.edit', $supplier) }}" class="btn-custom btn-ghost-custom btn-sm-custom" title="Ubah"><i class="bi bi-pencil"></i></a>
                                    @endif
                                    @if (auth()->user()->hasPermission('supplier.update'))
                                        <form action="{{ route('suppliers.destroy', $supplier) }}" method="POST" data-confirm="Hapus supplier ini?" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-custom btn-ghost-custom btn-sm-custom text-danger" title="Hapus"><i class="bi bi-trash"></i></button>
                                        </form>
                                    @endif
                                    @if (auth()->user()->hasPermission('supplier.deactivate'))
                                        <form action="{{ route('suppliers.toggle-status', $supplier) }}" method="POST" data-confirm="Ubah status supplier ini?">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn-custom btn-ghost-custom btn-sm-custom" title="{{ $supplier->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                                <i class="bi {{ $supplier->is_active ? 'bi-pause-circle' : 'bi-play-circle' }}"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty-state icon="bi-truck" title="Supplier tidak ditemukan" description="Tambahkan supplier baru atau ubah kata kunci pencarian." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>

    @if (auth()->user()->hasPermission('supplier.create'))
        <div class="modal-overlay" id="modalSupplierCreate">
            <div class="modal-custom" style="max-width:720px;">
                <div class="modal-custom__header">
                    <h3 style="margin:0;">Supplier Baru</h3>
                    <button type="button" class="icon-btn" data-modal-close aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="modal-custom__body">
                    @php $supplier = new \App\Models\Supplier(['is_active' => true]); @endphp
                    <form action="{{ route('suppliers.store') }}" method="POST">
                        @csrf
                        @include('suppliers._form')
                    </form>
                </div>
            </div>
        </div>

        @if ($errors->any())
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    var modal = document.getElementById('modalSupplierCreate');
                    if (modal) modal.classList.add('is-open');
                });
            </script>
        @endif
    @endif
@endsection
