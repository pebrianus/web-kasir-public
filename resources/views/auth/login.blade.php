@extends('layouts.auth')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        @if ($errors->has('username'))
            <div class="alert alert-danger alert-dismissible fade show text-center col-md-8" role="alert">
                {{ $errors->first('username') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        <div class="col-md-8">
            <div class="row auth-card">
                {{-- Kolom Kiri: Logo --}}
                <div class="col-md-5 left">
                    <img src="{{ asset('images/logo-rs.png') }}" alt="Logo Rumah Sakit">
                </div>

                {{-- Kolom Kanan: Form Login --}}
                <div class="col-md-7 right">
                    <h4 class="mb-4 text-center">Login Sistem</h4>

                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login.proses') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" name="username" id="username" class="form-control" required autofocus>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" name="password" id="password" class="form-control" required>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary">Login</button>
                        </div>
                    </form>
                </div>

            </div> <!-- end auth-card -->
        </div>
    </div>
</div>
@endsection
