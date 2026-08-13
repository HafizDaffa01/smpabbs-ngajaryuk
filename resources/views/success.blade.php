@extends('layouts.app')

@section('title', 'Berhasil - NgajarYuk')

@section('content')
<div class="container d-flex flex-column align-items-center justify-content-center py-5">
    <div class="row w-100 justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="status-card p-5 text-center animate__animated animate__zoomIn">
                <div class="status-icon-wrapper">
                    <i class="fas fa-check-circle"></i>
                </div>
                
                <h1 class="fw-800 mb-3 text-success">BERHASIL</h1>
                <p class="mb-4 text-muted fs-6">
                    <strong>Absensi berhasil disimpan.</strong> Terima kasih karena telah mengisi absensi.
                </p>

                <div class="d-flex flex-column gap-3 justify-content-center mt-2">
                    <a href="{{ route('absensi.index') }}" class="btn btn-success px-4 py-2 fw-600">
                        <i class="fas fa-undo-alt me-2"></i>Absen Lagi
                    </a>
                    <a href="/journal" class="btn btn-outline-custom px-4 py-2 fw-600">
                        <i class="fas fa-book me-2"></i>Ke Journal
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
