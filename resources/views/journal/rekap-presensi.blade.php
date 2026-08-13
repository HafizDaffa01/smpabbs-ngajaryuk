@extends('layouts.app')

@section('title', 'Rekap Presensi - ' . $grade)

@section('content')
<div class="rekap-page">

        <div class="position-relative">
            <button type="button" class="btn btn-outline-info btn-sm px-4 py-2 fw-bold"
                onclick="window.location.href = '{{ route('rekap.index') }}'">
                <i class="fas fa-chevron-left me-2"></i>Kembali
            </button>
        </div>

        <!-- Branding Header -->
        <div class="rekap-header">
            <h1>JOURNAL OF SUBJECT</h1>
            <h2>ABBS JUNIOR HIGH SCHOOL</h2>
        </div>

        <!-- Header Section -->
        <div class="d-flex align-items-center justify-content-between mb-5 flex-wrap gap-4 no-print mt-4">
            <div class="d-flex align-items-center">
                <h1 class="h2 fw-800 mb-0 me-3">{{ $grade }}</h1>
                <nav aria-label="breadcrumb" class="d-none d-md-block">
                    <ol class="breadcrumb mb-0 bg-transparent p-0">
                        <li class="breadcrumb-item"><a href="{{ route('rekap.index') }}"
                                class="text-info text-decoration-none fw-bold">Rekap</a>
                        </li>
                        <li class="breadcrumb-item active text-light opacity-75">Presensi Siswa</li>
                    </ol>
                </nav>
            </div>

            <div class="d-flex align-items-center flex-wrap gap-3">
                <div class="btn-group btn-group-sm no-print" role="group">
                    <a href="{{ route('rekap.showPresensi', ['class' => $grade, 'view' => 'semester', 'semester' => $semester, 'year' => $year]) }}"
                        class="btn {{ $viewType == 'semester' ? 'btn-info' : 'btn-outline-info' }} fw-bold">Semua</a>
                    <a href="{{ route('rekap.showPresensi', ['class' => $grade, 'view' => 'monthly', 'semester' => $semester, 'year' => $year, 'month' => $selectedMonth]) }}"
                        class="btn {{ $viewType == 'monthly' ? 'btn-info' : 'btn-outline-info' }} fw-bold">Per Bulan</a>
                </div>

                <form action="{{ route('rekap.showPresensi') }}" method="GET" id="semesterForm"
                    class="d-flex align-items-center gap-2 mb-0">
                    <input type="hidden" name="class" value="{{ $grade }}">
                    <input type="hidden" name="year" value="{{ $year }}">
                    <input type="hidden" name="view" value="{{ $viewType }}">
                    

                    @if($viewType == 'semester')
                        <span class="text-light small fw-bold">Semester:</span>
                        <select name="semester" class="form-select form-select-sm custom-dark-select" onchange="this.form.submit()">
                            <option value="1" {{ $semester == 1 ? 'selected' : '' }}>1 (Jan - Jun)</option>
                            <option value="2" {{ $semester == 2 ? 'selected' : '' }}>2 (Jul - Des)</option>
                        </select>
                    @else
                        <input type="hidden" name="semester" value="{{ $semester }}">
                        <span class="text-light small fw-bold">Bulan:</span>
                        <select name="month" class="form-select form-select-sm custom-dark-select" onchange="this.form.submit()">
                            @php
                                $mStart = ($semester == 1) ? 1 : 7;
                                $mEnd = ($semester == 1) ? 6 : 12;
                            @endphp
                            @for($m = $mStart; $m <= $mEnd; $m++)
                                <option value="{{ $m }}" {{ $selectedMonth == $m ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create(2000, $m, 1)->translatedFormat('F') }}
                                </option>
                            @endfor
                        </select>
                    @endif
                </form>

                @if(Auth::user()->is_admin)
                    <a href="{{ route('rekap.pdf', request()->all()) }}" target="_blank" class="btn btn-primary px-4 py-2 fw-bold shadow-sm no-print">
                        <i class="fas fa-print me-2"></i>Cetak PDF
                    </a>
                @endif
            </div>
        </div>

        @php
            $months = [];
            for ($m = $startMonth; $m <= $endMonth; $m++) {
                $months[] = $m;
            }
        @endphp

        @foreach ($months as $month)
            @php
                $carbonMonth = \Carbon\Carbon::create($year, $month, 1);
                $daysInMonth = $carbonMonth->daysInMonth;
            @endphp
            
            <div class="rekap-table-container mb-5">
                <div class="p-4 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-800 text-white opacity-90">
                            <i class="fas fa-calendar-alt me-2 text-info"></i>{{ $carbonMonth->translatedFormat('F Y') }}
                        </h5>
                    </div>
                </div>

                <div class="table-responsive p-0">
                    <table class="table table-bordered align-middle mb-0 rekap-table">
                        <thead>
                            <tr>
                                <th class="ps-3 text-center text-info col-no">NO</th>
                                <th class="ps-2 text-info col-nama">NAMA SISWA</th>
                                @for ($d = 1; $d <= 31; $d++)
                                    <th class="text-center text-info p-1 col-day {{ $d > $daysInMonth ? 'opacity-0' : '' }}">
                                        {{ $d }}
                                    </th>
                                @endfor
                                <th class="text-center text-success col-s">S</th>
                                <th class="text-center text-warning col-i">I</th>
                                <th class="text-center text-danger col-a">A</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($students as $index => $student)
                                <tr class="border-bottom">
                                    <td class="text-center text-muted small">{{ $index + 1 }}</td>
                                    <td class="ps-2 fw-bold text-truncate col-nama">{{ $student->name }}</td>
                                    @for ($d = 1; $d <= 31; $d++)
                                        @php
                                            $val = $attendanceMap[$student->id][$month][$d] ?? '';
                                            $statusClass = '';
                                            if ($val == 'S') $statusClass = 'status-s';
                                            elseif ($val == 'I') $statusClass = 'status-i';
                                            elseif ($val == 'A') $statusClass = 'status-a';
                                        @endphp
                                        <td class="text-center p-0 {{ $statusClass }} day-cell">
                                            {{ $val }}
                                        </td>
                                    @endfor
                                    
                                    @php
                                        $mS = 0; $mI = 0; $mA = 0;
                                        if (isset($attendanceMap[$student->id][$month])) {
                                            foreach ($attendanceMap[$student->id][$month] as $v) {
                                                if ($v == 'S') $mS++;
                                                elseif ($v == 'I') $mI++;
                                                elseif ($v == 'A') $mA++;
                                            }
                                        }
                                    @endphp
                                    <td class="text-center fw-bold status-s">{{ $mS ?: '' }}</td>
                                    <td class="text-center fw-bold status-i">{{ $mI ?: '' }}</td>
                                    <td class="text-center fw-bold status-a">{{ $mA ?: '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach

        <!-- Semester Summary Section -->
        <div class="rekap-table-container rekap-table-container-highlight shadow-lg border-radius-xl overflow-hidden mb-5">
            <div class="p-4 border-bottom">
                <h5 class="mb-0 fw-800 text-white">
                    <i class="fas fa-chart-pie me-2 text-info"></i>Ringkasan Absensi Semester {{ $semester }}
                </h5>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0 rekap-summary-table">
                    <thead>
                        <tr>
                            <th class="ps-4 py-3 text-info">NAMA SISWA</th>
                            <th class="text-center text-success">TOTAL SAKIT (S)</th>
                            <th class="text-center text-warning">TOTAL IJIN (I)</th>
                            <th class="text-center text-danger">TOTAL ALPHA (A)</th>
                            <th class="text-center text-info">TOTAL TIDAK HADIR</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($students as $student)
                            @php
                                $sS = $summary[$student->id]['S'] ?? 0;
                                $sI = $summary[$student->id]['I'] ?? 0;
                                $sA = $summary[$student->id]['A'] ?? 0;
                                $total = $sS + $sI + $sA;
                            @endphp
                            <tr class="border-bottom">
                                <td class="ps-4 fw-bold text-white">{{ $student->name }}</td>
                                <td class="text-center summary-s">{{ $sS }}</td>
                                <td class="text-center summary-i">{{ $sI }}</td>
                                <td class="text-center summary-a">{{ $sA }}</td>
                                <td class="text-center summary-total">{{ $total }} Hari</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection
