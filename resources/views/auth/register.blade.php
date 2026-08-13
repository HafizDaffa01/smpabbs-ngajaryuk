@extends('layouts.auth')

@section('title', 'Register - NgajarYuk')

@section('content')
    <div class="login-box">
        {{-- HERO SIDE --}}
        <div class="login-hero">
            <div class="hero-content">
                <div class="hero-logo-box">
                    <img src="{{ asset('abbs.png') }}" alt="SMP ABBS">
                </div>
                <h1 class="hero-title">Bergabunglah</h1>
                <p class="hero-desc">Daftar untuk mengakses sistem manajemen guru</p>
            </div>
        </div>

        {{-- FORM SIDE --}}
        <div class="login-form-area">
            <div class="form-header">
                <h2 class="form-title">Pendaftaran</h2>
                <p class="form-subtitle">Lengkapi data untuk mendaftar.</p>
            </div>

            @if ($errors->any())
                <div class="alert-danger-custom">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>Terdapat kesalahan pada inputan Anda.</span>
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <div class="form-group">
                    <label class="form-label" for="name">Pilih Nama Guru</label>
                    <div class="input-wrapper">
                        <select id="name" name="name" class="form-input @error('name') is-invalid @enderror" required>
                            <option value="" disabled selected>Pilih nama Anda...</option>
                            @forelse($guruList ?? [] as $guru)
                                <option value="{{ $guru }}">{{ $guru }}</option>
                            @empty
                                <option value="" disabled>Belum ada data guru</option>
                            @endforelse
                        </select>
                        <i class="fas fa-user input-icon"></i>
                    </div>
                    @error('name')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Alamat Email</label>
                    <div class="input-wrapper">
                        <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-input"
                            placeholder="nama@contoh.com" required autocomplete="off">
                        <i class="fas fa-envelope input-icon"></i>
                    </div>
                    @error('email')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="inputPassword">Kata Sandi</label>
                    <div class="input-wrapper">
                        <input type="password" id="inputPassword" name="password" class="form-input" placeholder="••••••••"
                            required autocomplete="off">
                        <i class="fas fa-lock input-icon"></i>
                    </div>
                    @error('password')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="password-confirm">Konfirmasi Kata Sandi</label>
                    <div class="input-wrapper">
                        <input type="password" id="password-confirm" name="password_confirmation" class="form-input" placeholder="••••••••"
                            required autocomplete="off">
                        <i class="fas fa-lock input-icon"></i>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <span>DAFTAR SEKARANG</span>
                    <i class="fas fa-user-plus"></i>
                </button>
            </form>

            <div class="login-foot">
                <p>Sudah memiliki akun? <a href="{{ route('login') }}">Masuk di sini.</a></p>
            </div>
        </div>
    </div>
@endsection
