@extends('layouts.auth')

@section('title', 'Login - NgajarYuk')

@section('content')
    <style>
        /* ----- Login Container ----- */
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

        /* ----- Hero Side ----- */
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

        /* ----- Form Side ----- */
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
            margin-bottom: 1.5rem;
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
            box-sizing: border-box;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-color, #3b82f6);
            background: #0f172a;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
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

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
            padding: 0;
            transition: color 0.2s ease;
        }

        .password-toggle:hover {
            color: #f8fafc;
        }

        .form-extras {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            color: #94a3b8;
            cursor: pointer;
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
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(59, 130, 246, 0.5);
        }

        .btn-submit:active {
            transform: translateY(0);
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
            margin-top: 2rem;
            text-align: center;
            color: #64748b;
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* ----- Mobile ----- */
        @media (max-width: 768px) {
            .login-box {
                flex-direction: column;
                max-width: 100%;
                min-height: auto;
                border-radius: 16px;
                margin: 0;
            }

            .login-hero {
                padding: 28px 20px;
            }

            .hero-logo-box {
                width: 72px;
                height: 72px;
                border-radius: 18px;
                margin-bottom: 1rem;
            }

            .hero-logo-box img {
                width: 48px;
            }

            .hero-title {
                font-size: 1.35rem;
                margin-bottom: 0.5rem;
            }

            .hero-desc {
                font-size: 0.85rem;
            }

            .login-form-area {
                padding: 24px 20px;
            }

            .form-title {
                font-size: 1.35rem;
            }

            .form-subtitle {
                font-size: 0.85rem;
                margin-bottom: 1.5rem;
            }

            .form-group {
                margin-bottom: 1.15rem;
            }

            .form-input {
                padding: 0.8rem 1rem;
                padding-left: 2.75rem;
                font-size: 16px; /* prevent iOS zoom */
            }

            .form-extras {
                margin-bottom: 1.25rem;
            }

            .btn-submit {
                padding: 0.9rem;
                font-size: 1rem;
                border-radius: 12px;
            }

            .login-foot {
                margin-top: 1.5rem;
                font-size: 0.8rem;
            }
        }

        @media (max-width: 380px) {
            .login-form-area {
                padding: 20px 16px;
            }

            .hero-title {
                font-size: 1.2rem;
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
