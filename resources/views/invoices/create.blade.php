@extends('layouts.app')

@section('title', 'Invoice Baru')

@section('content')
    <div class="page-header">
        <div>
            <ol class="breadcrumb-custom">
                <li><a href="{{ route('dashboard') }}">Beranda</a></li>
                <li class="sep">/</li>
                <li><a href="{{ route('invoices.index') }}">Invoice</a></li>
                <li class="sep">/</li>
                <li class="current">Baru</li>
            </ol>
            <h1 class="page-header__title">Invoice Baru</h1>
            <p class="page-header__desc">Catat tagihan dari supplier obat.</p>
        </div>
    </div>

    @if ($suppliers->isEmpty())
        <div class="alert-custom alert-warning-custom" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>Belum ada supplier aktif. <a href="{{ route('suppliers.create') }}">Tambahkan supplier terlebih dahulu</a>.</div>
        </div>
    @endif

    <form action="{{ route('invoices.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @include('invoices._form')
    </form>
@endsection