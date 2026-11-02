@extends('layouts.app')

@section('title', 'Ubah Pengguna')

@section('content')
    <div class="page-header">
        <div>
            <ol class="breadcrumb-custom">
                <li><a href="{{ route('dashboard') }}">Beranda</a></li>
                <li class="sep">/</li>
                <li><a href="{{ route('users.index') }}">Pengguna</a></li>
                <li class="sep">/</li>
                <li class="current">{{ $user->name }}</li>
            </ol>
            <h1 class="page-header__title">Ubah Pengguna</h1>
            <p class="page-header__desc">{{ $user->email }}</p>
        </div>
    </div>

    <div class="card-custom">
        <div class="card-custom__body">
            <form action="{{ route('users.update', $user) }}" method="POST">
                @csrf
                @method('PUT')
                @include('users._form')
            </form>
        </div>
    </div>
@endsection
