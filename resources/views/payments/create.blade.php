@extends('layouts.app')

@use('App\Support\Format')

@section('title', 'Pembayaran Baru')

@section('content')
    <div class="page-header">
        <div>
            <ol class="breadcrumb-custom">
                <li><a href="{{ route('dashboard') }}">Beranda</a></li>
                <li class="sep">/</li>
                <li><a href="{{ route('payments.index') }}">Pembayaran</a></li>
                <li class="sep">/</li>
                <li class="current">Baru</li>
            </ol>
            <h1 class="page-header__title">Pembayaran Baru</h1>
            <p class="page-header__desc">Seluruh nominal pembayaran wajib dialokasikan ke invoice dari supplier yang sama.</p>
        </div>
    </div>

    @include('payments._form')
@endsection