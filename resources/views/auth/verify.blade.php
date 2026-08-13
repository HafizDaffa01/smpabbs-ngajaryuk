@extends('layouts.auth')

@section('title', 'Verifikasi Email - NgajarYuk')

@section('content')
    <div class="login-box">
        {{-- HERO SIDE --}}
        <div class="login-hero">
            <div class="hero-content">
                <div class="hero-logo-box">
                    <img src="{{ asset('abbs.png') }}" alt="SMP ABBS">
                </div>
                <h1 class="hero-title">Verifikasi Email</h1>
                <p class="hero-desc">Satu langkah lagi untuk menyelesaikan pendaftaran Anda</p>
            </div>
        </div>

        {{-- FORM SIDE --}}
        <div class="login-form-area">
            <div class="form-header">
                <h2 class="form-title">Cek Inbox Anda</h2>
                <p class="form-subtitle">Tautan verifikasi telah dikirimkan.</p>
            </div>

            @if (session('resent'))
                <div class="alert-success-custom">
                    <i class="fas fa-check-circle"></i>
                    <span>Tautan verifikasi baru telah dikirim ke alamat email Anda.</span>
                </div>
            @endif

            <div class="verification-message">
                Sebelum melanjutkan, harap periksa email Anda untuk mengklik tautan verifikasi. <br><br>
                Jika Anda tidak menerima email tersebut, klik tombol di bawah untuk meminta tautan baru.
            </div>

            <form class="d-inline" method="POST" action="{{ route('verification.resend') }}">
                @csrf
                <button type="submit" class="btn-submit">
                    <span>KIRIM ULANG TAUTAN</span>
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </div>
@endsection
