@extends('layouts.app')

@section('title', 'Ubah Supplier')

@section('content')
    <div class="page-header">
        <div>
            <ol class="breadcrumb-custom">
                <li><a href="{{ route('dashboard') }}">Beranda</a></li>
                <li class="sep">/</li>
                <li><a href="{{ route('suppliers.index') }}">Supplier</a></li>
                <li class="sep">/</li>
                <li class="current">Ubah</li>
            </ol>
            <h1 class="page-header__title">Ubah Supplier</h1>
            <p class="page-header__desc">{{ $supplier->name }}</p>
        </div>
    </div>

    <div class="card-custom">
        <div class="card-custom__body">
            <form action="{{ route('suppliers.update', $supplier) }}" method="POST">
                @csrf
                @method('PUT')
                @include('suppliers._form')
            </form>
        </div>
    </div>
@endsection
