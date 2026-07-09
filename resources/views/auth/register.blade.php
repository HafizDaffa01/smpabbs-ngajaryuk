@extends('layouts.auth')

@section('title', 'Register - NgajarYuk')

@section('content')
    <style>
        .login-box {
            display: flex;
            width: 100%;
            max-width: 900px;
            background: #1e293b;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
            overflow: hidden;
            min-height: 580px;
            border: 1px solid #334155;
        }

        .login-hero {
            flex: 1;
            background: linear-gradient(135deg, var(--primary-color, #3b82f6) 0%, var(--primary-hover, #2563eb) 100%);
            padding: 60px 40px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .login-hero::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            top: -100px;
            left: -100px;
        }

        .login-hero::after {
            content: '';
            position: absolute;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            bottom: -50px;
            right: -50px;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-logo-box {
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            margin-left: auto;
            margin-right: auto;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .hero-logo-box img {
            width: 70px;
            filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.2));
        }

        .hero-title {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 1rem;
            letter-spacing: -0.02em;
        }

        .hero-desc {
            font-size: 1.1rem;
            opacity: 0.9;
            font-weight: 500;
            max-width: 280px;
            margin: 0 auto;
        }

        .login-form-area {
            flex: 1;
            padding: 50px 45px;
            background: #1e293b;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-title {
            font-size: 1.75rem;
            font-weight: 800;
            color: #f8fafc;
            margin-bottom: 0.5rem;
        }

        .form-subtitle {
            color: #94a3b8;
            margin-bottom: 2.5rem;
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-size: 0.85rem;
            font-weight: 700;
            color: #cbd5e1;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .input-wrapper {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 0.85rem 1rem;
            padding-left: 2.75rem;
            border-radius: 12px;
            border: 2px solid #334155;
            background: #0f172a;
            transition: all 0.2s ease;
            font-size: 1rem;
            color: #f1f5f9;
            font-weight: 500;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-color, #3b82f6);
            background: #0f172a;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
        }

        select.form-input {
            padding-left: 2.75rem !important;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 1rem;
            transition: color 0.2s ease;
        }

        .form-input:focus+.input-icon {
            color: var(--primary-color, #3b82f6);
        }

        .btn-submit {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--primary-color, #3b82f6) 0%, var(--primary-hover, #2563eb) 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 1.5rem;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(59, 130, 246, 0.5);
        }

        .alert-danger-custom {
            background: rgba(225, 29, 72, 0.1);
            color: #fb7185;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
            font-weight: 600;
            border: 1px solid rgba(225, 29, 72, 0.2);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .login-foot {
            margin-top: 1.5rem;
            text-align: center;
            color: #64748b;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .login-foot a {
            color: var(--primary-color);
            text-decoration: none;
        }

        @media (max-width: 768px) {
            .login-box {
                flex-direction: column;
                max-width: 450px;
                min-height: auto;
            }

            .login-hero {
                padding: 40px 20px;
            }

            .login-form-area {
                padding: 40px 30px;
            }

            .hero-title {
                font-size: 1.5rem;
            }

            .hero-logo-box {
                width: 80px;
                height: 80px;
                margin-bottom: 1.5rem;
            }

            .hero-logo-box img {
                width: 55px;
            }
        }
    </style>

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
