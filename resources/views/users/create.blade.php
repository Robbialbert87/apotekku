@extends('layouts.app')

@section('title', 'Pengguna Baru')

@section('content')
    <div class="page-header">
        <div>
            <ol class="breadcrumb-custom">
                <li><a href="{{ route('dashboard') }}">Beranda</a></li>
                <li class="sep">/</li>
                <li><a href="{{ route('users.index') }}">Pengguna</a></li>
                <li class="sep">/</li>
                <li class="current">Baru</li>
            </ol>
            <h1 class="page-header__title">Pengguna Baru</h1>
            <p class="page-header__desc">Buat akun admin atau karyawan.</p>
        </div>
    </div>

    <div class="card-custom">
        <div class="card-custom__body">
            <form action="{{ route('users.store') }}" method="POST">
                @csrf
                @include('users._form')
            </form>
        </div>
    </div>
@endsection
