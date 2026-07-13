@extends('layouts.app')

@section('title', 'Edit Profil - ' . config('app.name'))

@section('content')
<div class="container pb-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card border-0 shadow-2xl rounded-2xl overflow-hidden" style="background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.05) !important;">
                <div class="card-header border-0 py-5 text-center position-relative overflow-hidden" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(37, 99, 235, 0.1) 100%);">
                    <div class="position-absolute top-0 start-0 w-100 h-100" style="background: radial-gradient(circle at 20% 30%, rgba(59, 130, 246, 0.05) 0%, transparent 50%); pointer-events: none;"></div>
                    
                    <div class="mb-3 d-inline-block position-relative">
                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center shadow-lg mx-auto" style="width: 80px; height: 80px; border: 4px solid var(--bg-card);">
                            <i class="fas fa-user-edit text-white" style="font-size: 2rem;"></i>
                        </div>
                        <div class="position-absolute bottom-0 end-0 bg-success rounded-circle border border-white" style="width: 18px; height: 18px; border-width: 3px !important;"></div>
                    </div>
                    
                    <h3 class="mb-1 fw-bold text-white">Edit Profil</h3>
                    <p class="text-muted small mb-0">Kelola dan perbarui informasi personal Anda</p>
                </div>

                <div class="card-body p-4 p-md-5">
                    @if(session('success'))
                        <div class="alert alert-success border-0 bg-soft-success mb-4 py-3 d-flex align-items-center rounded-xl animate__animated animate__fadeIn">
                            <i class="fas fa-check-circle me-3 fa-lg"></i>
                            <div class="fw-bold">{{ session('success') }}</div>
                        </div>
                    @endif

                    <form action="{{ route('profile.update_new') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <!-- Nama (Disabled) -->
                        <div class="mb-4">
                            <label for="name" class="form-label ms-1 fw-bold small text-uppercase tracking-wider text-primary opacity-75">Nama Lengkap</label>
                            <div class="custom-input-group disabled">
                                <div class="icon-box">
                                    <i class="fas fa-user text-primary"></i>
                                </div>
                                <input type="text" id="name" class="custom-form-control" value="{{ $user->name }}" disabled>
                                <div class="status-box">
                                    <i class="fas fa-lock text-muted" style="font-size: 0.8rem;"></i>
                                </div>
                            </div>
                            <div class="form-text ms-1 text-muted small opacity-50">Nama hanya dapat diubah oleh Admin.</div>
                        </div>

                        <!-- Email (Disabled) -->
                        <div class="mb-4">
                            <label for="email" class="form-label ms-1 fw-bold small text-uppercase tracking-wider text-primary opacity-75">Alamat Email</label>
                            <div class="custom-input-group disabled">
                                <div class="icon-box">
                                    <i class="fas fa-envelope text-info"></i>
                                </div>
                                <input type="email" id="email" class="custom-form-control" value="{{ $user->email }}" disabled>
                                <div class="status-box">
                                    <i class="fas fa-lock text-muted" style="font-size: 0.8rem;"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Nomor Telepon (Editable) -->
                        <div class="mb-5">
                            <label for="phone_num" class="form-label ms-1 fw-bold small text-uppercase tracking-wider text-primary opacity-75">Nomor WhatsApp</label>
                            <div class="custom-input-group @error('phone_num') is-invalid-group @enderror">
                                <div class="icon-box">
                                    <i class="fab fa-whatsapp text-success fa-lg"></i>
                                </div>
                                <input type="text" name="phone_num" id="phone_num" 
                                       class="custom-form-control @error('phone_num') is-invalid @enderror" 
                                       placeholder="Gunakan format 08xxxxxxxxxx" 
                                       value="{{ old('phone_num', $user->phone_num) }}"
                                       autofocus>
                                @error('phone_num')
                                    <div class="status-box text-danger me-3">
                                        <i class="fas fa-exclamation-circle"></i>
                                    </div>
                                @enderror
                            </div>
                            @error('phone_num')
                                <div class="text-danger small mt-1 ms-1 fw-medium">{{ $message }}</div>
                            @else
                                <div class="form-text ms-1 text-muted small opacity-50">Pastikan nomor ini aktif untuk menerima alert.</div>
                            @enderror
                        </div>

                        <div class="d-grid gap-3">
                            <button type="submit" class="btn btn-primary btn-lg rounded-xl py-3 fw-bold shadow-lg transition-all hover-lift">
                                <i class="fas fa-save me-2"></i> Simpan Perubahan
                            </button>
                            <a href="{{ url('/') }}" class="btn btn-dark bg-soft-dark border-0 rounded-xl py-2 text-muted fw-bold transition-all">
                                <i class="fas fa-arrow-left me-2"></i> Kembali ke Dashboard
                            </a>
                            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-danger btn-lg rounded-xl py-3 fw-bold transition-all">
                                    <i class="fas fa-sign-out-alt me-2"></i> Keluar
                                </button>
                            </form>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    :root {
        --input-bg: #0f172a;
        --input-border: #334155;
    }

    .tracking-wider {
        letter-spacing: 0.1em;
    }

    .custom-input-group {
        display: flex;
        align-items: center;
        background-color: var(--input-bg);
        border: 1.5px solid var(--input-border);
        border-radius: 14px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
    }

    .custom-input-group:focus-within:not(.disabled) {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
        background-color: rgba(15, 23, 42, 0.8);
    }

    .custom-input-group.disabled {
        opacity: 0.8;
        background-color: rgba(15, 23, 42, 0.4);
        cursor: not-allowed;
    }

    .custom-input-group.is-invalid-group {
        border-color: var(--danger-color);
    }

    .icon-box {
        width: 58px;
        display: flex;
        justify-content: center;
        align-items: center;
        /* Hapus border di sisi kanan icon-box untuk menghindari garis putih */
        border-right: none !important;
    }

    .custom-form-control {
        flex: 1;
        background: transparent;
        border: none;
        color: var(--text-main);
        padding: 0.95rem 1rem;
        font-weight: 500;
        outline: none;
        width: 100%;
        font-size: 1rem;
    }

    .custom-form-control:disabled {
        color: var(--text-muted);
        cursor: not-allowed;
    }

    .status-box {
        padding-right: 1.25rem;
        display: flex;
        align-items: center;
    }

    .hover-lift:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px -5px rgba(59, 130, 246, 0.4) !important;
    }

    .rounded-xl {
        border-radius: 12px !important;
    }

    .rounded-2xl {
        border-radius: 20px !important;
    }

    .shadow-2xl {
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5) !important;
    }

    .transition-all {
        transition: all 0.2s ease;
    }

    .bg-soft-dark {
        background-color: rgba(30, 41, 59, 0.5);
    }

    /* Hilangkan outline default kalau ada */
    .custom-input-group span, .custom-input-group input {
        border: none !important;
        outline: none !important;
        box-shadow: none !important;
    }
</style>
@endsection
