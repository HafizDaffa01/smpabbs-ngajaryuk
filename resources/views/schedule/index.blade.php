@extends('layouts.app')

@section('title', 'Manajemen Jadwal')

@section('content')
    <div class="container pb-5">
        <div class="d-flex align-items-center mb-4">
            <h1 class="h3 fw-800 text-primary m-0 text-uppercase tracking-tight">
                <i class="fas fa-calendar-alt me-2"></i>Manajemen Jadwal
            </h1>
        </div>

        {{-- Alert Messages --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-xl mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle me-3 fa-lg"></i>
                    <div>{{ session('success') }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row g-4 mb-5">
            {{-- Filter Section --}}
            <div class="col-lg-7">
                <div class="card border-0 h-100">
                    <div class="card-header bg-transparent pt-4 pb-2 border-0">
                        <h5 class="mb-0 fw-800 text-main text-white">
                            <i class="fas fa-filter me-2 text-primary"></i>Filter Jadwal
                        </h5>
                    </div>
                    <div class="card-body pt-2">
                        <form method="GET" action="{{ route('schedule.index') }}" class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-700 small text-white">KELAS</label>
                                <select name="class" class="form-select" onchange="this.form.submit()">
                                    @foreach ($classes as $class)
                                        <option value="{{ $class }}"
                                            {{ $selectedClass == $class ? 'selected' : '' }}>
                                            {{ $class }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-700 small text-white">HARI</label>
                                <select name="day" class="form-select" onchange="this.form.submit()">
                                    @foreach ($days as $day)
                                        <option value="{{ $day }}" {{ $selectedDay == $day ? 'selected' : '' }}>
                                            {{ $day }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        {{-- Schedule Table --}}
        <div class="card border-0">
            <div class="card-header bg-transparent py-4 border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-800 text-main text-white">
                    <i class="fas fa-table me-2 text-primary"></i>Daftar Pelajaran: <span
                        class="text-primary">{{ $selectedClass }}</span>
                    <span class=" mx-2">|</span>
                    <span class="">{{ $selectedDay }}</span>
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="py-3 small fw-800 text-uppercase ps-4"></th>
                                <th class="py-3 small fw-800 text-uppercase">Mata Pelajaran</th>
                                <th class="pe-4 py-3 small fw-800 text-uppercase">Guru Pengampu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($schedules as $schedule)
                                <tr>
                                    <td class="ps-4">
                                        <span class="badge bg-soft-primary text-primary">-</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            {{-- <span class="badge bg-info text-white me-2">{{ $schedule->subject }}</span> --}}
                                            <span class="text-main fw-bold">{{ $schedule->subject_display }}</span>
                                        </div>
                                    </td>
                                    <td class="pe-4">
                                        <div class="d-flex align-items-center ">
                                            <i class="fas fa-chalkboard-teacher me-2 opacity-50"></i>
                                            @if ($schedule->subject_display === 'LEADERSHIP' || $schedule->subject === 'LEADERSHIP')
                                                <span class="text-primary italic small fw-500">- Sesuai kelompok leadership
                                                    masing masing -</span>
                                            @else
                                                {{ $schedule->teacher ?? 'Belum Ditentukan' }}
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-5">
                                        <div class="py-4">
                                            <i class="fas fa-calendar-times fa-4x  mb-3 opacity-25"></i>
                                            <h5 class=" fw-600">Jadwal Kosong</h5>
                                            <p class=" small">Tidak ada mata pelajaran yang dijadwalkan untuk
                                                filter ini.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <style>
        .table-responsive {
            max-height: 600px;
            overflow-y: auto;
        }

        /* Dark Table Overrides matching TS Manager */
        .table {
            --bs-table-bg: transparent !important;
            --bs-table-color: var(--text-main) !important;
            color: var(--text-main) !important;
            border-collapse: collapse !important;
            background: transparent !important;
            margin-bottom: 0;
        }

        .table thead tr {
            background: rgba(255, 255, 255, 0.05) !important;
        }

        .table th {
            border-bottom: 2px solid var(--border-color) !important;
            color: var(--text-main) !important;
            font-weight: 800 !important;
            text-transform: uppercase;
            font-size: 0.75rem;
            padding: 1.2rem 0.75rem !important;
            background: transparent !important;
        }

        .table td {
            border-bottom: 1px solid var(--border-color) !important;
            padding: 1rem 0.75rem !important;
            color: var(--text-main) !important;
            background: transparent !important;
        }

        .table tbody tr,
        .table tbody td {
            background-color: transparent !important;
            transition: background 0.2s ease;
        }

        .table-hover tbody tr:hover {
            background: rgba(255, 255, 255, 0.04) !important;
        }

        .table-hover tbody tr:hover td {
            color: var(--text-main) !important;
        }

        .badge {
            font-size: 0.75rem;
            padding: 0.35em 0.65em;
        }

        .card {
            border: none;
            border-radius: 12px;
            background: rgba(30, 41, 59, 0.7) !important;
            backdrop-filter: blur(8px);
            overflow: hidden;
        }

        .card-header {
            border-radius: 12px 12px 0 0 !important;
            background: rgba(255, 255, 255, 0.03) !important;
            border-bottom: 1px solid var(--border-color) !important;
        }

        .btn {
            border-radius: 8px;
        }

        /* Modal Styles matching TS Manager */
        .modal-content {
            border-radius: 12px;
            border: 1px solid var(--border-color);
            background: var(--bg-card);
        }

        .modal-header {
            border-radius: 12px 12px 0 0;
        }

        .modal-body {
            background: var(--bg-card);
            color: var(--text-main);
        }

        .modal-footer {
            background: var(--bg-card);
            border-top: 1px solid var(--border-color);
        }
    </style>
@endsection
