@extends('layouts.app')

@section('title', 'Ubah Invoice')

@section('content')
    <div class="page-header">
        <div>
            <ol class="breadcrumb-custom">
                <li><a href="{{ route('dashboard') }}">Beranda</a></li>
                <li class="sep">/</li>
                <li><a href="{{ route('invoices.index') }}">Invoice</a></li>
                <li class="sep">/</li>
                <li><a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a></li>
                <li class="sep">/</li>
                <li class="current">Ubah</li>
            </ol>
            <h1 class="page-header__title">Ubah Invoice</h1>
            <p class="page-header__desc">{{ $invoice->invoice_number }} · {{ $invoice->supplier->name }}</p>
        </div>
    </div>

    <div class="card-custom">
        <div class="card-custom__body">
            <form action="{{ route('invoices.update', $invoice) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('invoices._form')
            </form>
        </div>
    </div>
@endsection
