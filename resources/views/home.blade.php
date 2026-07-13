@extends('layouts.app')

@section('title', 'Beranda - NgajarYuk')

@section('content')
<style>
    .home-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        box-shadow: var(--card-shadow);
        border-radius: var(--card-radius);
    }
    .icon-wrapper {
        width: 80px;
        height: 80px;
        background: rgba(59, 130, 246, 0.1);
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1.5rem;
    }
    .icon-wrapper i {
        font-size: 2.5rem;
        color: var(--primary-color);
    }
    .btn-outline-custom {
        color: var(--text-main);
        border: 1px solid var(--border-color);
        background: transparent;
    }
    .btn-outline-custom:hover {
        background: rgba(255, 255, 255, 0.05);
        color: white;
    }
    .alert-success-custom {
        background: rgba(16, 185, 129, 0.1);
        color: #34d399;
        padding: 1rem;
        border-radius: 12px;
        margin-bottom: 1.5rem;
        font-size: 0.95rem;
        font-weight: 600;
        border: 1px solid rgba(16, 185, 129, 0.2);
    }
</style>

<div class="container" style="min-height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 15px;">
    <div class="row w-100 justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="home-card p-5 text-center animate__animated animate__zoomIn">
                <div class="icon-wrapper">
                    <i class="fas fa-check-circle"></i>
                </div>
                
                <h2 class="fw-800 text-main mb-3" style="color: #fff;">Selamat Datang!</h2>
                <p class="mb-4" style="color: #94a3b8; font-size: 1.05rem;">
                    Anda telah berhasil masuk ke sistem Jurnal Kelas. Silakan gunakan menu navigasi untuk mengakses fitur-fitur yang tersedia.
                </p>
                
                @if (session('status'))
                    <div class="alert-success-custom" role="alert">
                        <i class="fas fa-info-circle me-2"></i> {{ session('status') }}
                    </div>
                @endif

                <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center mt-2">
                    <a href="{{ route('journal.selectClass') }}" class="btn btn-primary px-4 py-2" style="font-weight: 600;">
                        <i class="fas fa-book me-2"></i>Buka Jurnal
                    </a>
                    <a href="{{ route('schedule.index') }}" class="btn btn-outline-custom px-4 py-2" style="font-weight: 600;">
                        <i class="fas fa-calendar-alt me-2"></i>Lihat Jadwal
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
