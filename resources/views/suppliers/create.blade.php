@extends('layouts.app')

@section('title', 'Supplier Baru')

@section('content')
    <div class="page-header">
        <div>
            <ol class="breadcrumb-custom">
                <li><a href="{{ route('dashboard') }}">Beranda</a></li>
                <li class="sep">/</li>
                <li><a href="{{ route('suppliers.index') }}">Supplier</a></li>
                <li class="sep">/</li>
                <li class="current">Baru</li>
            </ol>
            <h1 class="page-header__title">Supplier Baru</h1>
            <p class="page-header__desc">Lengkapi informasi supplier obat.</p>
        </div>
    </div>

    <div class="card-custom">
        <div class="card-custom__body">
            <form action="{{ route('suppliers.store') }}" method="POST">
                @csrf
                @include('suppliers._form')
            </form>
        </div>
    </div>
@endsection
