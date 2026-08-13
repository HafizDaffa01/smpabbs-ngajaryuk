@extends('layouts.auth')

@section('title', 'Login - NgajarYuk')

@section('content')
    <div class="login-box">
        {{-- HERO SIDE --}}
        <div class="login-hero">
            <div class="hero-content">
                <div class="hero-logo-box">
                    <img src="{{ asset('abbs.png') }}" alt="SMP ABBS">
                </div>
                <h1 class="hero-title">NgajarYuk!</h1>
                <p class="hero-desc">Sistem Manajemen Jurnal & Absensi Modern</p>
            </div>
        </div>

        {{-- FORM SIDE --}}
        <div class="login-form-area">
            <div class="form-header">
                <h2 class="form-title">Selamat Datang</h2>
                <p class="form-subtitle">Silakan masuk untuk melanjutkan.</p>
            </div>

            @if ($errors->any())
                <div class="alert-danger-custom">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>Email atau kata sandi salah</span>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="form-group">
                    <label class="form-label" for="email">Alamat Email</label>
                    <div class="input-wrapper">
                        <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-input"
                            placeholder="nama@contoh.com" required autofocus>
                        <i class="fas fa-envelope input-icon"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="inputPassword">Kata Sandi</label>
                    <div class="input-wrapper">
                        <input type="password" id="inputPassword" name="password" class="form-input" placeholder="••••••••"
                            required>
                        <i class="fas fa-lock input-icon"></i>
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i id="passwordIcon" class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-extras">
                    <label class="remember-me">
                        <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                        <span>Ingat saya</span>
                    </label>
                </div>

                <button type="submit" class="btn-submit">
                    <span>MASUK SEKARANG</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>

            <div class="login-foot">
                <p>Butuh bantuan? Silakan hubungi admin IT.</p>
                <p class="build-version">Build Version: {{ env('BVer', '2.01') }}</p>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('inputPassword');
            const icon = document.getElementById('passwordIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }
    </script>
@endsection
