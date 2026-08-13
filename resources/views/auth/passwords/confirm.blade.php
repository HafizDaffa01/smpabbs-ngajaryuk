@extends('layouts.auth')

@section('title', 'Konfirmasi Password - NgajarYuk')

@section('content')
    <div class="login-box">
        {{-- HERO SIDE --}}
        <div class="login-hero">
            <div class="hero-content">
                <div class="hero-logo-box">
                    <img src="{{ asset('abbs.png') }}" alt="SMP ABBS">
                </div>
                <h1 class="hero-title">Konfirmasi Password</h1>
                <p class="hero-desc">Sistem membutuhkan konfirmasi identitas Anda</p>
            </div>
        </div>

        {{-- FORM SIDE --}}
        <div class="login-form-area">
            <div class="form-header">
                <h2 class="form-title">Keamanan Tambahan</h2>
                <p class="form-subtitle">Harap konfirmasi password sebelum melanjutkan.</p>
            </div>

            <form method="POST" action="{{ route('password.confirm') }}">
                @csrf

                <div class="form-group">
                    <label class="form-label" for="password">Kata Sandi</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" class="form-input" placeholder="••••••••"
                            required autocomplete="current-password" autofocus>
                        <i class="fas fa-lock input-icon"></i>
                    </div>
                    @error('password')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn-submit">
                    <span>KONFIRMASI</span>
                    <i class="fas fa-check"></i>
                </button>
            </form>

            <div class="login-foot">
                <p>Lupa password Anda? <a href="{{ route('password.request') }}">Reset di sini.</a></p>
            </div>
        </div>
    </div>
@endsection
