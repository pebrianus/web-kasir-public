{{-- Beritahu Laravel untuk pakai layout main kita --}}
@extends('layouts.main')

{{-- Isi 'title' di layout --}}
@section('title', 'Dashboard Kasir')

{{-- Isi 'content' di layout --}}
@section('content')

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-body">
                <h3>Hello, {{ Auth::user()->nama }}! Login Anda Berhasil!</h3>
                <p>Ini adalah halaman dashboard yang dimuat di dalam layout utama.</p>
            </div>
        </div>
    </div>
</div>

@endsection