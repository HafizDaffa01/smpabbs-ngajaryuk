@extends('layouts.app')

@section('title', 'Error - NgajarYuk')

@section('content')
<div class="container d-flex align-items-center justify-content-center py-5 min-h-70vh">
    <div class="row w-100 justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="status-card-error p-5 text-center animate__animated animate__zoomIn">
                <div class="status-icon-wrapper">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                
                <h1 class="fw-800 mb-3 text-danger">ERROR</h1>
                <p class="mb-4 text-muted">
                    <strong>{{ session('error') ?? 'Terjadi kesalahan sistem.' }}</strong>
                </p>

                <div class="d-flex flex-column gap-3 justify-content-center mt-2">
                    <a href="{{ route('absensi.index') }}" class="btn btn-danger px-4 py-2 fw-600">
                        <i class="fas fa-arrow-left me-2"></i>Kembali
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
