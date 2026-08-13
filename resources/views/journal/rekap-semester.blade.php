@extends('layouts.app')

@section('title', 'Rekap Semester - ' . $grade)

@section('content')
    <div class="rekap-page container-fluid py-4">


        <div class="position-relative">
            <button type="button" class="btn btn-outline-info btn-sm px-4 py-2 fw-bold btn-touch"
                onclick="window.location.href = '{{ route('rekap.index') }}'">
                <i class="fas fa-chevron-left me-2"></i>Kembali
            </button>

        </div>

        <!-- Branding Header -->
        <div class="text-center mb-5 mt-2">
            <h1 class="h2 fw-900 text-white mb-1 text-spacing-wide">JOURNAL OF SUBJECT</h1>
            <h2 class="h5 fw-700 text-info opacity-75 mb-3">ABBS JUNIOR HIGH SCHOOL</h2>
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
                        {{-- <li class="breadcrumb-item active text-light opacity-75">{{ $grade }}</li> --}}
                    </ol>
                </nav>
            </div>

            <div class="d-flex align-items-center flex-wrap gap-3">
                <div class="btn-group btn-group-sm no-print" role="group">
                    <a href="{{ route('rekap.show', ['class' => $grade, 'view' => 'semester', 'semester' => $semester, 'year' => $year]) }}"
                        class="btn {{ $viewType == 'semester' ? 'btn-info' : 'btn-outline-info' }} fw-bold">Semua</a>
                    <a href="{{ route('rekap.show', ['class' => $grade, 'view' => 'daily', 'semester' => $semester, 'year' => $year]) }}"
                        class="btn {{ $viewType == 'daily' ? 'btn-info' : 'btn-outline-info' }} fw-bold">Per Hari</a>
                </div>

                <form action="{{ route('rekap.show') }}" method="GET" id="semesterForm"
                    class="d-flex align-items-center gap-2 mb-0">
                    <input type="hidden" name="class" value="{{ $grade }}">
                    <input type="hidden" name="year" value="{{ $year }}">
                    <input type="hidden" name="view" value="{{ $viewType }}">
                    <div @if($viewType == 'daily') hidden @endif>
                        <span class="text-light small fw-bold">Semester:</span>
                        <select name="semester" class="form-select form-select-sm custom-dark-select" onchange="this.form.submit()" 
                            @if($viewType == 'daily') hidden @endif>
                            <option value="1" {{ $semester == 1 ? 'selected' : '' }}>1 (Jan - Jun)</option>
                            <option value="2" {{ $semester == 2 ? 'selected' : '' }}>2 (Jul - Des)</option>
                        </select>
                    </div>
                </form>

                @if ($viewType == 'daily')
                    <div class="d-flex align-items-center gap-2">
                        @php
                            $currDateObj = \Carbon\Carbon::parse($selectedDate);
                            $prevDay = (clone $currDateObj)->subDay();
                            $nextDay = (clone $currDateObj)->addDay();
                        @endphp

                        <a href="{{ route('rekap.show', ['class' => $grade, 'view' => 'daily', 'date' => $prevDay->toDateString(), 'semester' => $semester]) }}"
                            class="btn btn-dark btn-sm border-secondary text-info">
                            <i class="fas fa-chevron-left"></i>
                        </a>

                        <div class="position-relative">
                            <button type="button" id="btnCalendarRekap" class="btn btn-outline-info btn-sm px-4 py-2 fw-bold btn-touch text-nowrap">
                                <i class="fas fa-calendar-alt me-2"></i>{{ $currDateObj->translatedFormat('d F Y') }}
                            </button>
                            <input type="text" id="flatpickr-rekap"
                                class="flatpickr-hidden">
                        </div>

                        <a href="{{ route('rekap.show', ['class' => $grade, 'view' => 'daily', 'date' => $nextDay->toDateString(), 'semester' => $semester]) }}"
                            class="btn btn-dark btn-sm border-secondary text-info">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                @endif
                
                @if(Auth::user()->is_admin)
                    <a href="{{ route('rekap.kbm.pdf', request()->all()) }}" target="_blank" class="btn btn-primary px-4 py-2 fw-bold shadow-sm no-print">
                        <i class="fas fa-print me-2"></i>Cetak PDF
                    </a>
                @endif
            </div>
        </div>

        <!-- Table Container -->
        <div class="rekap-table-container shadow-lg rounded-xl overflow-hidden mb-5">
            <div class="p-4 border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-800 text-white opacity-90">Rekap Jurnal & KBM</h5>
                    <div class="text-info small fw-bold opacity-75">
                        <i class="fas fa-info-circle me-1"></i> Menampilkan histori kegiatan belajar dan siswa yang absen.
                    </div>
                </div>
            </div>

            <div class="table-responsive p-0">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4 py-3 border-0 text-info col-tanggal">Tanggal</th>
                            <th class="py-3 border-0 text-info col-mapel">Mata
                                Pelajaran</th>
                            <th class="py-3 border-0 text-info col-guru">Guru
                            </th>
                            <th class="py-3 border-0 text-info">Materi KBM</th>
                            <th class="pe-4 py-3 border-0 text-info col-absensi">Siswa
                                Absen</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @php
                            if ($viewType == 'daily') {
                                $startDateObj = \Carbon\Carbon::parse($selectedDate);
                                $endDateObj = $startDateObj->copy();
                            } else {
                                $startDateObj = \Carbon\Carbon::create($year, $startMonth, 1);
                                $endDateObj = $startDateObj->copy()->month($endMonth)->endOfMonth();
                            }
                            $anyDataShown = false;
                        @endphp

                        @for ($date = $startDateObj->copy(); $date <= $endDateObj; $date->addDay())
                            @php
                                $dateStr = $date->toDateString();
                                $dayOfWeek = $date->format('l');
                                $daySchedules = $schedulesByDay->get($dayOfWeek, collect())->sortBy('period');
                                $dayNotes = $kbmData->get($dateStr, collect());
                                $dayAbsents = $absentsByDate[$dateStr] ?? [];

                                $rowsToShow = [];

                                if ($daySchedules->isNotEmpty()) {
                                    foreach ($daySchedules as $sched) {
                                        $subjDisplay = $sched->subject_display ?: $sched->subject;
                                        $matchingNote = $dayNotes->firstWhere('subject', $subjDisplay);

                                        $rowsToShow[] = [
                                            'subject' => $subjDisplay,
                                            'teacher' => $sched->teacher ?: $matchingNote->teacher->name ?? 'N/A',
                                            'note' => $matchingNote->note ?? null,
                                        ];
                                    }

                                    // Extra notes not in schedule
                                    foreach ($dayNotes as $dn) {
                                        if (
                                            !$daySchedules->contains(
                                                fn($s) => ($s->subject_display ?: $s->subject) == $dn->subject,
                                            )
                                        ) {
                                            $rowsToShow[] = [
                                                'subject' => $dn->subject,
                                                'teacher' => $dn->teacher->name ?? 'N/A',
                                                'note' => $dn->note,
                                            ];
                                        }
                                    }
                                } elseif ($dayNotes->isNotEmpty()) {
                                    foreach ($dayNotes as $dn) {
                                        $rowsToShow[] = [
                                            'subject' => $dn->subject,
                                            'teacher' => $dn->teacher->name ?? 'N/A',
                                            'note' => $dn->note,
                                        ];
                                    }
                                } elseif (!empty($dayAbsents)) {
                                    $rowsToShow[] = [
                                        'subject' => '-',
                                        'teacher' => '-',
                                        'note' => null,
                                    ];
                                }

                                $rowCount = count($rowsToShow);

                                // FALLBACK: Always show the date even if no schedules/notes/absents
                                if ($rowCount === 0) {
                                    $rowsToShow[] = [
                                        'subject' => '-',
                                        'teacher' => '-',
                                        'note' => null,
                                    ];
                                    $rowCount = 1;
                                }

                                $anyDataShown = true;
                            @endphp

                            @foreach ($rowsToShow as $idx => $row)
                                <tr class="kbm-row">
                                    @if ($idx === 0)
                                        <td class="ps-4" rowspan="{{ $rowCount }}">
                                            <div class="d-flex flex-column">
                                                <span class="fw-bold fs-6 text-white">
                                                    {{ $date->translatedFormat('d F Y') }}
                                                </span>
                                                <small class="text-muted opacity-75 fw-600">{{ $date->translatedFormat('l') }}</small>
                                            </div>
                                        </td>
                                    @endif

                                    <td>
                                        <span class="badge bg-info text-dark fw-bold px-3 py-2 badge-rekap">{{ $row['subject'] }}</span>
                                    </td>
                                    <td class="fw-bold text-white opacity-90 fs-6">{{ $row['teacher'] }}</td>
                                    <td>
                                        @if ($row['note'])
                                            <div class="p-3 rounded shadow-sm border kbm-note">
                                                {{ $row['note'] }}
                                            </div>
                                        @else
                                            <span class="text-muted small italic opacity-40">
                                                <i class="fas fa-edit me-1"></i> Belum ada keterangan KBM
                                            </span>
                                        @endif
                                    </td>

                                    @if ($idx === 0)
                                        <td class="pe-4" rowspan="{{ $rowCount }}">
                                            <div class="d-flex flex-wrap gap-2">
                                                @forelse($dayAbsents as $abs)
                                            @php
                                                $absentClass = 'absent-s';
                                                if ($abs['value'] == 'I') {
                                                    $absentClass = 'absent-i';
                                                }
                                                if ($abs['value'] == 'A') {
                                                    $absentClass = 'absent-a';
                                                }
                                            @endphp
                                            <span class="badge px-2 py-1 {{ $absentClass }}">
                                                        {{ $abs['name'] }} ({{ $abs['value'] }})
                                                    </span>
                                                @empty
                                                    <span class="text-success small fw-bold italic opacity-75">
                                                        <i class="fas fa-check-circle me-1"></i> Semua Hadir
                                                    </span>
                                                @endforelse
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        @endfor

                        @if (!$anyDataShown)
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="fas fa-calendar-times text-warning mb-3 fs-1 opacity-25"></i>
                                        <p class="text-muted fs-5">Tidak ada data KBM atau jadwal pada semester ini</p>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        

        <style>
            @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap');

            :root {
                --primary-color: #60a5fa;
                --bg-page: #0f172a;
                --bg-card: #1e293b;
                --border-color: rgba(148, 163, 184, 0.15);
                --text-main: #e2e8f0;
                --text-muted: #94a3b8;
                --accent-cyan: #7dd3fc;
            }

            body {
                font-family: 'Inter', sans-serif;
                background-color: var(--bg-page) !important;
                color: var(--text-main) !important;
            }

            .table,
            .table> :not(caption)>*>* {
                background-color: var(--bg-page) !important;
                color: var(--text-main) !important;
                border-color: var(--border-color) !important;
            }

            .text-info {
                color: var(--accent-cyan) !important;
            }

            .bg-info {
                background-color: var(--accent-cyan) !important;
            }

            .bg-opacity-10 {
                background-color: rgba(125, 211, 252, 0.1) !important;
            }

            .container-fluid {
                background-color: var(--bg-page);
            }

            .kbm-row:hover {
                background-color: rgba(30, 41, 59, 0.5) !important;
                transition: background-color 0.2s ease;
            }

            .nav-arrow {
                font-size: 3.5rem;
                color: var(--primary-color);
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                text-decoration: none !important;
                filter: drop-shadow(0 0 8px rgba(96, 165, 250, 0.2));
            }

            .nav-arrow:hover {
                color: #93c5fd;
                transform: scale(1.1);
                filter: drop-shadow(0 0 12px rgba(147, 197, 253, 0.4));
            }

            .rekap-table-container {
                backdrop-filter: blur(20px);
                animation: slideUp 0.6s ease-out;
            }

            .custom-dark-select:focus {
                box-shadow: 0 0 0 0.25rem rgba(13, 202, 240, 0.25);
                border-color: #0dcaf0;
            }

            @keyframes slideUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            @media print {

                .navbar,
                .btn,
                .breadcrumb,
                footer,
                .no-print,
                .nav-arrow {
                    display: none !important;
                }

                .container-fluid {
                    width: 100% !important;
                    padding: 0 !important;
                    background: white !important;
                }

                body {
                    background: white !important;
                    color: black !important;
                    font-size: 10pt;
                }

                .rekap-table-container {
                    border: 1px solid #000 !important;
                    box-shadow: none !important;
                    background: white !important;
                    overflow: visible !important;
                }

                .table {
                    color: black !important;
                    width: 100% !important;
                }

                .table thead {
                    background: #f0f0f0 !important;
                    border-bottom: 2px solid #000 !important;
                }

                .table thead th {
                    color: black !important;
                    padding: 10px 5px !important;
                }

                .table td {
                    border-bottom: 1px solid #ddd !important;
                    padding: 10px 5px !important;
                }

                .text-white {
                    color: black !important;
                }

                .text-info {
                    color: #000 !important;
                    text-decoration: underline;
                }

                .badge {
                    border: 1px solid #000 !important;
                    color: black !important;
                    background: transparent !important;
                    font-weight: bold !important;
                }

                .rounded {
                    background: transparent !important;
                    border: 1px solid #ccc !important;
                    color: black !important;
                }
            }
        </style>

        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const flatpickrHidden = document.getElementById('flatpickr-rekap');
                    if (flatpickrHidden) {
                        flatpickr(flatpickrHidden, {
                            clickOpens: false,
                            dateFormat: "Y-m-d",
                            defaultDate: "{{ $selectedDate }}",
                            locale: "id",
                            onChange: function(selectedDates, dateStr) {
                                window.location.href =
                                    `/prevSmes/show?class={{ $grade }}&view=daily&date=${dateStr}&semester={{ $semester }}`;
                            }
                        });

                        document.getElementById('btnCalendarRekap').addEventListener('click', () => {
                            flatpickrHidden._flatpickr.open();
                        });
                    }
                });
            </script>
        @endpush
    </div>
@endsection
