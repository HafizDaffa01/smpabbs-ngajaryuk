@extends('layouts.auth')

@section('title', 'Lupa Password - NgajarYuk')

@section('content')
    <div class="login-box">
        {{-- HERO SIDE --}}
        <div class="login-hero">
            <div class="hero-content">
                <div class="hero-logo-box">
                    <img src="{{ asset('abbs.png') }}" alt="SMP ABBS">
                </div>
                <h1 class="hero-title">Reset Password</h1>
                <p class="hero-desc">Dapatkan tautan reset password via email Anda</p>
            </div>
        </div>

        {{-- FORM SIDE --}}
        <div class="login-form-area">
            <div class="form-header">
                <h2 class="form-title">Lupa Password?</h2>
                <p class="form-subtitle">Masukkan email terdaftar Anda.</p>
            </div>

            @if (session('status'))
                <div class="alert-success-custom">
                    <i class="fas fa-check-circle"></i>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                <div class="form-group">
                    <label class="form-label" for="email">Alamat Email</label>
                    <div class="input-wrapper">
                        <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-input"
                            placeholder="nama@contoh.com" required autocomplete="email" autofocus>
                        <i class="fas fa-envelope input-icon"></i>
                    </div>
                    @error('email')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn-submit">
                    <span>KIRIM TAUTAN RESET</span>
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>

            <div class="login-foot">
                <p>Ingat password Anda? <a href="{{ route('login') }}">Masuk di sini.</a></p>
            </div>
        </div>
    </div>
@endsection
