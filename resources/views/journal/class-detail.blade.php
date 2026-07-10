@extends('layouts.app')

<title>Jurnal Kelas {{ $grade }}</title>

@section('content')
    <script>
        let hasUnsaved = false;
        let skipUnloadWarning = false;
    </script>

    <style>
        :root {
            --status-sakit-bg: rgba(59, 130, 246, 0.15);
            --status-sakit-text: #60a5fa;
            --status-ijin-bg: rgba(245, 158, 11, 0.15);
            --status-ijin-text: #fbbf24;
            --status-alpha-bg: rgba(239, 68, 68, 0.15);
            --status-alpha-text: #f87171;
        }

        /* Highlight header tanggal */
        th.tgl-selected {
            background: linear-gradient(135deg, var(--primary-color), #2563eb) !important;
            color: white !important;
            border: none !important;
            font-weight: 800;
            box-shadow: inset 0 -4px 0 rgba(0, 0, 0, 0.1);
        }

        /* Highlight cell tanggal siswa */
        td.td-selected {
            background-color: rgba(59, 130, 246, 0.08) !important;
            color: var(--primary-color) !important;
            border: 1px solid rgba(59, 130, 246, 0.3) !important;
            font-weight: 700;
        }

        #journalContainer {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.3);
            margin: 2rem auto;
            max-width: 1400px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .page-header {
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .page-header h3 {
            font-size: 1.85rem;
            font-weight: 800;
            background: linear-gradient(to right, #fff, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 0;
        }

        .back-link {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-muted);
            text-decoration: none;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-color);
        }

        .back-link:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.08);
            transform: translateX(-4px);
            border-color: var(--text-muted);
        }

        .filter-section {
            padding: 1.25rem;
            background: rgba(255, 255, 255, 0.02);
            border-radius: 12px;
            margin-bottom: 2.5rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1.25rem;
            align-items: center;
            border: 1px solid rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
        }

        .input-group-text {
            background: rgba(255, 255, 255, 0.03) !important;
            border-color: var(--border-color) !important;
            color: var(--text-muted) !important;
        }

        .form-control {
            background: #0f172a !important;
            border-color: var(--border-color) !important;
            color: #fff !important;
            font-weight: 500;
        }

        .form-control:focus {
            border-color: var(--primary-color) !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15) !important;
        }

        /* Update Teacher/Subject Table */
        .teacher-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 2.5rem;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.01);
        }

        .teacher-table th {
            background: rgba(255, 255, 255, 0.03);
            padding: 1.25rem 1rem;
            text-align: left;
            font-weight: 800;
            color: var(--text-muted);
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            border-bottom: 1px solid var(--border-color);
        }

        .teacher-table td {
            padding: 1.5rem 1rem;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-main);
            vertical-align: middle;
            transition: background 0.2s ease;
        }

        .teacher-table tbody tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }

        .kbm-link {
            text-decoration: none !important;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 8px 14px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid transparent;
            display: inline-block;
            transition: all 0.2s ease;
        }

        .kbm-link:hover {
            background: rgba(59, 130, 246, 0.1);
            border-color: rgba(59, 130, 246, 0.2);
            color: var(--primary-color) !important;
        }

        /* Attendance Table Refinements - Non-Scrollable */
        .table-wrapper {
            border-radius: 12px;
            border: 1px solid var(--border-color);
            overflow: auto;
            /* Enable scroll if needed */
            background: var(--bg-card);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .table {
            background: transparent !important;
            color: var(--text-main) !important;
            margin-bottom: 0;
            table-layout: fixed;
            /* Fit all columns */
            width: 100%;
        }

        .table.table-bordered th {
            background: rgba(30, 41, 59, 1) !important;
            border: 1px solid var(--border-color) !important;
            color: #94a3b8;
            font-weight: 800;
            padding: 0.5rem 2px;
            font-size: 0.65rem;
            text-align: center;
            text-transform: uppercase;
        }

        .table.table-bordered td {
            border: 1px solid var(--border-color) !important;
            padding: 0.5rem 2px;
            text-align: center;
            font-size: 0.75rem;
            vertical-align: middle;
            color: var(--text-main);
            background: transparent !important;
            transition: all 0.2s ease;
        }

        /* Column Widths */
        .col-no {
            width: 35px;
        }

        .col-nama {
            width: 160px;
            text-align: left !important;
            padding-left: 8px !important;
        }

        .col-status-sum {
            width: 30px;
            font-weight: 800;
        }

        .table tbody tr:hover td {
            background: rgba(255, 255, 255, 0.02) !important;
        }

        /* Status Coloring */
        .bg-soft-success {
            background: var(--status-sakit-bg) !important;
            color: var(--status-sakit-text) !important;
        }

        .bg-soft-warning {
            background: var(--status-ijin-bg) !important;
            color: var(--status-ijin-text) !important;
        }

        .bg-soft-danger {
            background: var(--status-alpha-bg) !important;
            color: var(--status-alpha-text) !important;
        }

        .editable-cell {
            cursor: pointer;
            position: relative;
        }

        .editable-cell span {
            font-weight: 700;
            font-size: 0.85rem;
        }

        .editable-cell:hover {
            background: rgba(255, 255, 255, 0.05) !important;
        }

        #rekapKeterangan {
            padding: 1.5rem;
            background: rgba(16, 185, 129, 0.03);
            border-left: 4px solid #10b981;
            font-size: 0.95rem;
            color: #d1fae5;
            backdrop-filter: blur(5px);
        }

        #rekapKeterangan strong {
            display: block;
            margin-bottom: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.1em;
            color: #34d399;
        }

        select {
            background: #1e293b !important;
            color: #fff !important;
            border: 1px solid var(--primary-color) !important;
            border-radius: 4px;
            padding: 2px 4px;
        }

        /* Today Highlight */
        .table.table-bordered th.today-highlight,
        .table.table-bordered td.today-highlight {
            background: rgba(14, 165, 233, 0.08) !important;
            border-left: 1.5px solid rgba(14, 165, 233, 0.5) !important;
            border-right: 1.5px solid rgba(14, 165, 233, 0.5) !important;
            position: relative;
        }

        th.today-highlight {
            background: rgba(14, 165, 233, 0.2) !important;
            color: #38bdf8 !important;
            font-weight: 900 !important;
            border-top: 2px solid #0ea5e9 !important;
        }

        th.today-highlight::after {
            content: 'HARI INI';
            position: absolute;
            top: -14px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 7px;
            color: #0ea5e9;
            font-weight: 900;
            white-space: nowrap;
            letter-spacing: 0.5px;
            text-shadow: 0 0 10px rgba(14, 165, 233, 0.5);
        }

        /* Combined Highlight & Selection */
        th.today-highlight.tgl-selected,
        td.today-highlight.td-selected {
            background: rgba(14, 165, 233, 0.4) !important;
            color: white !important;
        }

        th.today-highlight.tgl-selected {
            background: #0ea5e9 !important;
        }

        @media (max-width: 768px) {
            #journalContainer {
                padding: 1.25rem;
                margin: 0.5rem;
                border-radius: 8px;
            }

            .page-header {
                flex-direction: column;
                align-items: stretch;
                gap: 1.25rem;
                text-align: center;
            }

            .page-header h3 {
                font-size: 1.4rem;
                line-height: 1.4;
            }

            .filter-section {
                flex-direction: column;
                align-items: stretch;
                padding: 1rem;
                gap: 1rem;
            }

            .filter-section>* {
                max-width: 100% !important;
                width: 100% !important;
            }

            .input-group {
                max-width: 100% !important;
            }

            /* Tables for Mobile */
            .table-wrapper {
                margin: 0 -1rem 1.5rem -1rem;
                /* Full width break-out */
                border-radius: 0;
                border-left: none;
                border-right: none;
            }

            .table {
                min-width: 800px;
                /* Ensure 31 columns are readable */
            }

            .teacher-table {
                min-width: 600px;
                /* Ensure teacher table is readable */
            }

            .teacher-table td,
            .teacher-table th {
                padding: 1rem 0.75rem;
            }

            .col-nama {
                width: 140px;
            }

            /* Sticky Name Column for Attendance */
            .table thead tr:first-child th:nth-child(2),
            .table tbody tr td:nth-child(2) {
                position: sticky;
                left: 35px;
                /* Offset by No column */
                background: #0f172a !important;
                z-index: 10;
                box-shadow: 2px 0 5px rgba(0, 0, 0, 0.2);
            }

            .table thead tr:first-child th:first-child,
            .table tbody tr td:first-child {
                position: sticky;
                left: 0;
                background: #1e293b !important;
                z-index: 11;
            }

            #rekapKeterangan {
                padding: 1rem;
                font-size: 0.85rem;
            }
        }

        /* Extreme Mobile (300px - 400px) */
        @media (max-width: 400px) {
            #journalContainer {
                padding: 1rem 0.75rem;
            }

            .page-header h3 {
                font-size: 1.2rem;
            }

            .badge {
                padding: 0.4rem 0.6rem;
                font-size: 0.75rem;
            }
        }
    </style>

    <div class="container-fluid" id="journalContainer">
        
        <!-- Branding Header -->
        <div class="text-center mb-5">
            <h1 class="h2 fw-900 text-white mb-1" style="letter-spacing: 2px;">JOURNAL OF SUBJECT</h1>
            <h2 class="h5 fw-700 text-info opacity-75 mb-3">ABBS JUNIOR HIGH SCHOOL</h2>
        </div>

        {{-- ================= Header ================= --}}
        <div class="page-header">
            <div>
                <h3>
                    @if (in_array($grade, ['7', '8', '9']))
                        <i data-feather="users" class="me-2"></i>Jurnal Leadership Class {{ $grade }}
                    @else
                        Jurnal Kelas {{ $grade }}
                    @endif
                </h3>
                <p class="mb-0 mt-2"
                    style="color: var(--text-muted); font-weight: 600; font-size: 0.95rem; display: flex; align-items: center; gap: 8px;">
                    <i data-feather="calendar" style="width: 16px; height: 16px;"></i>
                    {{ \Carbon\Carbon::create($year, $month, $day)->translatedFormat('l, d F Y') }}
                </p>
            </div>
            <a href="/journal" class="back-link">
                <i data-feather="arrow-left"></i> Kembali ke Daftar Kelas
            </a>
        </div>

        {{-- ================= Filter Atas ================= --}}
        <div class="filter-section no-print">
            <input type="hidden" name="usr" value="{{ $usr }}">

            <div class="d-flex flex-wrap gap-2 align-items-center">
                @php
                    $currentDate = \Carbon\Carbon::create($year, $month, $day);
                    $prevDate = (clone $currentDate)->subDay();
                    $nextDate = (clone $currentDate)->addDay();
                @endphp

                <a href="{{ route('journal.show', ['class' => $grade, 'day' => $prevDate->day, 'month' => $prevDate->month, 'year' => $prevDate->year, 'usr' => $usr]) }}"
                    class="btn btn-outline-primary btn-sm px-3">
                    <i class="fas fa-angle-double-left me-2"></i>Tanggal Sebelumnya
                </a>

                <a href="{{ route('journal.show', ['class' => $grade, 'day' => $nextDate->day, 'month' => $nextDate->month, 'year' => $nextDate->year, 'usr' => $usr]) }}"
                    class="btn btn-outline-primary btn-sm px-3">
                    Tanggal Selanjutnya<i class="fas fa-angle-double-right ms-2"></i>
                </a>

                <div class="position-relative">
                    <button type="button" id="btnCalendar" class="btn btn-outline-primary btn-sm px-3">
                        <i class="fas fa-calendar-alt me-2"></i>{{ $currentDate->translatedFormat('d F Y') }}
                    </button>
                    <input type="text" id="flatpickr-date" value="{{ sprintf('%04d-%02d-%02d', $year, $month, $day) }}"
                        style="position:absolute; opacity:0; pointer-events:none; left:0; bottom:0; width:100%;">
                </div>

                <div class="ms-md-auto d-flex gap-2">
                    <button id="btnSave" class="btn btn-primary"><i data-feather="save"></i> Simpan</button>

                    @if (Auth::user()->is_admin)
                        <a href="{{ route('journal.export', [
                            'class' => $grade,
                            'month' => $month,
                            'year' => $year,
                            'day' => $day,
                        ]) }}"
                            class="btn btn-info">
                            <i data-feather="download"></i> Export Excel
                        </a>
                    @endif
                </div>
            </div>
        </div>

        @if ($teachers->isEmpty())
            <div class="text-center py-5 my-5">
                <i class="fas fa-calendar-times fa-4x mb-3 opacity-25"></i>
                <h5 class="fw-800">Jadwal Kosong</h5>
                <p class="text-muted">Tidak ada mata pelajaran yang dijadwalkan untuk hari ini
                    ({{ ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'][\Carbon\Carbon::create($year, $month, $day)->dayOfWeek] }}).
                </p>
            </div>
        @else
            {{-- =================== TABEL MAPEL / GURU =================== --}}
            <div class="table-wrapper" style="margin-bottom: 24px;">
                <table class="teacher-table">
                    <thead>
                        <tr>
                            <th style="width:160px">Mapel / Mata Pelajaran</th>
                            <th style="width:200px">Nama Guru</th>
                            <th>Materi KBM</th>
                            <th>Daftar Absen</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($teachers as $index => $t)
                            @php
                                $mapelData = $t->mapel[$grade] ?? null;

                                // Kalau array, ambil satu (atau join)
                                if (is_array($mapelData)) {
                                    $sub = $mapelData[0] ?? null;
                                } else {
                                    $sub = $mapelData;
                                }

                                $note = $sub && isset($noteIndexed[$sub]) ? $noteIndexed[$sub] : null;

                                // PERMISSION CHECK: Admin bisa edit semua, Guru cuma bisa edit miliknya sendiri
                                $canEditKbm = Auth::user()->is_admin || Auth::id() == $t->id;
                            @endphp


                            <tr>
                                <td>{{ $sub }}</td>
                                <td>{{ $t->name }}</td>

                                {{-- ====== KBM ====== --}}
                                <td @if ($canEditKbm) onclick="addKeterangan(this)" @endif
                                    data-subject="{{ $sub }}" data-teacher="{{ $t->id }}">

                                    <div class="d-flex align-items-center justify-content-between gap-2">
                                        @if ($canEditKbm)
                                            <a href="#" role="button" tabindex="0" class="kbm-link"
                                                style="color:{{ $note ? 'var(--info-color)' : 'var(--text-muted)' }}"
                                                onclick="addKeterangan(this.closest('td')); event.preventDefault();">
                                                @if ($note)
                                                    {!! nl2br(e($note->note)) !!}
                                                @else
                                                    <i data-feather="edit-2" style="width: 16px; height: 16px; display: inline; vertical-align: -2px;"></i> Klik untuk tambah keterangan
                                                @endif
                                            </a>
                                        @else
                                            <div class="kbm-link disabled"
                                                style="color:{{ $note ? 'var(--info-color)' : 'var(--text-muted)' }}; cursor: default; background: transparent; border: none; padding: 0;">
                                                @if ($note)
                                                    {!! nl2br(e($note->note)) !!}
                                                @else
                                                    -
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                {{-- ====== KOLOM KETERANGAN (HANYA SEKALI) ====== --}}
                                @if ($loop->first)
                                    <td style="max-width: 300px; width: 300px; word-wrap: break-word;"
                                        rowspan="{{ count($teachers) }}" id="rekapKeterangan">
                                        <em class="text-muted">Tidak ada siswa absen</em>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>


                </table>
            </div>



            <div class="input-group me-auto" style="max-width: 300px;">
                <span class="input-group-text bg-transparent border-end-0 text-muted">
                    <i data-feather="search" style="width: 16px; height: 16px;"></i>
                </span>
                <input type="text" id="searchStudent" class="form-control border-start-0 ps-0"
                    placeholder="Cari nama siswa...">
            </div>

            {{-- ================= Tabel Absensi ================= --}}
            <div class="table-wrapper">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th rowspan="2" class="col-no">No</th>
                            <th rowspan="2" class="col-nama">Nama Siswa</th>
                            <th colspan="31" id="tgl"
                                style="background: linear-gradient(135deg, rgba(14, 165, 233, 0.15), rgba(14, 165, 233, 0.05)); color: #38bdf8; font-weight: 600;">
                                {{ \Carbon\Carbon::create($year, $month, 1)->translatedFormat('F Y') }}</th>
                            <th rowspan="2" class="col-status-sum">S</th>
                            <th rowspan="2" class="col-status-sum">I</th>
                            <th rowspan="2" class="col-status-sum">A</th>
                        </tr>
                        <tr>
                            @php
                                $currentDay = (int) date('d');
                                $isCurrentMonthYear = $month == date('m') && $year == date('Y');
                            @endphp
                            @for ($i = 1; $i <= 31; $i++)
                                <th style="width: 35px;"
                                    class="{{ $isCurrentMonthYear && $i == $currentDay ? 'today-highlight' : '' }}">
                                    {{ $i }}
                                </th>
                            @endfor
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($students as $index => $student)
                            <tr>
                                <td class="col-no">{{ $index + 1 }}</td>
                                <td class="col-nama">{{ $student->name }}</td>

                                @for ($i = 1; $i <= 31; $i++)
                                    <td class="editable-cell {{ $isCurrentMonthYear && $i == $currentDay ? 'today-highlight' : '' }}"
                                        data-student="{{ $student->id }}" data-day="{{ $i }}">
                                        <span
                                            style="display: inline-block; min-width: 20px;">{{ $attendance[$student->id][$i] ?? '' }}</span>
                                    </td>
                                @endfor

                                <td class="bg-soft-success col-status-sum">
                                    {{ $summary[$student->id]['S'] ?? 0 }}</td>
                                <td class="bg-soft-warning col-status-sum">
                                    {{ $summary[$student->id]['I'] ?? 0 }}</td>
                                <td class="bg-soft-danger col-status-sum">
                                    {{ $summary[$student->id]['A'] ?? 0 }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif




    </div>

    <script>
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000,
        });
    </script>

    {{-- ================== EDITABLE DROPDOWN ================== --}}
    <script>
        function highlightSelectedDate() {
            const dateStr = '{{ sprintf('%04d-%02d-%02d', $year, $month, $day) }}';
            const date = new Date(dateStr);
            const selectedDay = date.getDate();

            // Scope clearing/highlighting to the main attendance table only
            document.querySelectorAll('.table.table-bordered thead th').forEach(th => th.classList.remove('tgl-selected'));
            document.querySelectorAll('.table.table-bordered tbody td.editable-cell').forEach(td => td.classList.remove(
                'td-selected'));

            // Highlight day header (second row of the attendance table thead)
            const ths = document.querySelectorAll('.table.table-bordered thead tr:nth-child(2) th');
            ths.forEach((th, index) => {
                if (index === selectedDay - 1) {
                    th.classList.add('tgl-selected');
                }
            });

            // Highlight cells in the selected column
            document.querySelectorAll('.table.table-bordered tbody tr').forEach(tr => {
                const td = tr.querySelector(`td.editable-cell[data-day='${selectedDay}']`);
                if (td) td.classList.add('td-selected');
            });
        }

        // Jalankan saat load
        highlightSelectedDate();

        // Update saat tanggal diganti
        const flatpickrDateInput = document.getElementById('flatpickr-date');
        if (flatpickrDateInput) {
            flatpickr(flatpickrDateInput, {
                clickOpens: false,
                dateFormat: "Y-m-d",
                defaultDate: "{{ sprintf('%04d-%02d-%02d', $year, $month, $day) }}",
                locale: "id",
                onChange: function(selectedDates, dateStr) {
                    const params = new URLSearchParams(window.location.search);
                    const usr = params.get('usr');
                    const date = selectedDates[0];
                    const d = date.getDate();
                    const m = date.getMonth() + 1;
                    const y = date.getFullYear();
                    window.location.href =
                        `/journal/show?class={{ $grade }}&month=${m}&year=${y}&day=${d}&usr=${usr}`;
                }
            });

            document.getElementById('btnCalendar').addEventListener('click', () => {
                flatpickrDateInput._flatpickr.open();
            });
        }


        document.querySelectorAll('.editable-cell').forEach(cell => {
            cell.addEventListener('click', function() {
                if (cell.querySelector('select')) return;

                const currentValue = cell.textContent.trim();
                cell.innerHTML = '';

                const select = document.createElement('select');
                select.innerHTML = `
            <option value="" selected disabled>-</option>
            <option value="S" ${currentValue === 'S' ? 'selected' : ''}>Sakit</option>
            <option value="I" ${currentValue === 'I' ? 'selected' : ''}>Ijin</option>
            <option value="A" ${currentValue === 'A' ? 'selected' : ''}>Alpha</option>
        `;
                cell.appendChild(select);
                select.focus();

                let valueChanged = false;

                select.addEventListener('change', () => {
                    valueChanged = true;
                    if (cell.contains(select)) {
                        cell.innerHTML =
                            `<span style="display: inline-block; min-width: 20px;">${select.value}</span>`;
                        hasUnsaved = true;
                        Toast.fire({
                            icon: 'success',
                            title: 'Absensi berhasil diubah!'
                        });
                        buildKeterangan();
                    }
                });

                select.addEventListener('blur', () => {
                    // Only update if select is still in the cell AND no change event fired
                    if (cell.contains(select) && !valueChanged) {
                        cell.innerHTML =
                            `<span style="display: inline-block; min-width: 20px;">${select.value}</span>`;
                    }
                });
            });
        });

        // === SEARCH STUDENT ===
        document.getElementById('searchStudent')?.addEventListener('input', function() {
            const query = this.value.toLowerCase();
            document.querySelectorAll('.table.table-bordered tbody tr').forEach(row => {
                const nameCell = row.querySelector('td:nth-child(2)');
                if (nameCell) {
                    const name = nameCell.textContent.toLowerCase();
                    row.style.display = name.includes(query) ? '' : 'none';
                }
            });
        });
    </script>


    updateHeaderText();



    </script>

    <script>
        function addKeterangan(ts) {

            const p = ts.querySelector("a");
            const isPlaceholder = p.textContent.includes("Klik untuk tambah");

            // Simpan isi lama untuk restore ketika Cancel
            const originalText = p.textContent;
            const originalColor = p.style.color;

            // Kalau masih placeholder → kosongkan dulu
            if (isPlaceholder) {
                p.textContent = "";
                p.style.color = "var(--info-color)";
            }

            // Ambil waktu sekarang sebagai default nilai input time
            const now = new Date();
            const jam = now.getHours().toString().padStart(2, '0');
            const menit = now.getMinutes().toString().padStart(2, '0');
            const defaultTime = `${jam}:${menit}`;

            // Ambil teks lama tanpa [xx:xx]
            const oldText = p ? p.textContent : "";
            const oldKet = oldText.replace(/^\s*\[\d{2}:\d{2}\]\s*/, "").trim();


            Swal.fire({
                title: '<i data-feather="edit-2" style="width: 20px; height: 20px; display: inline; vertical-align: -3px; margin-right: 8px;"></i>Tambah Keterangan KBM',
                html: `
            <!-- <div hidden style="text-align: left; margin-bottom: 16px;">
                <label style="font-size:14px; font-weight: 600; color: #1e293b; display: block; margin-bottom: 8px;"><i data-feather="clock" style="width: 16px; height: 16px; display: inline; vertical-align: -2px; margin-right: 4px;"></i>Waktu Mulai:</label>
                <input id="timeInput" type="time" value="${defaultTime}" 
                       style="margin-top:0; padding:10px; font-size:15px; width:100%; max-width: 200px; border: 2px solid #cbd5e1; border-radius: 6px;">
            </div> -->
            <input id="timeInput" type="hidden" value="${defaultTime}">
        `,
                input: "text",
                inputPlaceholder: "Contoh: Guru tidak hadir / Materi halaman 21",
                inputValue: oldKet,
                inputAttributes: {
                    style: "padding: 10px; border-radius: 6px; border: 2px solid #cbd5e1; margin-top: 12px;"
                },

                showCancelButton: true,
                confirmButtonText: "<i data-feather=\"save\" style=\"width: 16px; height: 16px; display: inline; vertical-align: -2px; margin-right: 4px;\"></i>Simpan",
                cancelButtonText: "<i data-feather=\"x\" style=\"width: 16px; height: 16px; display: inline; vertical-align: -2px; margin-right: 4px;\"></i>Batal",
                confirmButtonColor: '#3b82f6',
                cancelButtonColor: '#ef4444',

                preConfirm: () => {
                    // const waktuDipilih = document.getElementById("timeInput").value;
                    // if (!waktuDipilih) return Swal.showValidationMessage("<i data-feather=\"alert-circle\" style=\"width: 16px; height: 16px; display: inline; vertical-align: -2px; margin-right: 4px;\"></i>Waktu tidak boleh kosong!");
                    const waktuDipilih = document.getElementById("timeInput").value;
                    return {
                        time: waktuDipilih,
                        ket: Swal.getInput().value
                    };
                }
            }).then((result) => {

                // ===================== CANCEL =====================
                if (result.dismiss === Swal.DismissReason.cancel ||
                    result.dismiss === Swal.DismissReason.esc ||
                    result.dismiss === Swal.DismissReason.backdrop) {

                    p.textContent = originalText;
                    p.style.color = originalColor;
                    return;
                }


                // ===================== CONFIRM =====================
                if (result.isConfirmed) {

                    const waktu = result.value.time;
                    const ket = (result.value.ket || "").trim();

                    if (ket === "") {
                        // Jika user hapus isinya → tampilkan placeholder lagi
                        p.innerHTML =
                            "<i data-feather=\"edit-2\" style=\"width: 16px; height: 16px; display: inline; vertical-align: -2px; margin-right: 4px;\"></i>Klik untuk tambah keterangan";
                        p.style.color = "#94a3b8";
                        return;
                    }

                    // Simpan isi baru
                    // p.textContent = `[${waktu}] ${ket}`;
                    p.textContent = `${ket}`;
                    p.style.color = "var(--info-color)";

                    hasUnsaved = true;
                    Toast.fire({
                        icon: 'success',
                        title: 'KBM berhasil ditambahkan!'
                    });
                }
            });
        }
    </script>


    {{-- ================= SIMPAN DATA ================= --}}

    {{-- ================= PERINGATAN KELUAR ================= --}}
    <script>
        window.addEventListener('beforeunload', (e) => {
            if (hasUnsaved && !skipUnloadWarning) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    </script>
    <script>
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {

                // Jangan ganggu textarea / input
                if (['TEXTAREA', 'INPUT', 'SELECT'].includes(e.target.tagName)) return;

                // Jika SweetAlert terbuka
                if (document.querySelector('.swal2-container')) {
                    const confirmBtn = document.querySelector('.swal2-confirm');
                    if (confirmBtn) confirmBtn.click();
                    return;
                }

                // Jika ada perubahan
                if (hasUnsaved) {
                    e.preventDefault();
                    document.getElementById('btnSave').click();
                }
            }
        });
        document.getElementById('btnSave').addEventListener('click', async function() {

            const params = new URLSearchParams(window.location.search);
            const className = params.get('class');

            // Gunakan data dari PHP sebagai angka (integer)
            const selectedDay = {{ $day }};
            const selectedMonth = {{ $month }};
            const selectedYear = {{ $year }};

            const dateStr =
                `${selectedYear}-${selectedMonth.toString().padStart(2, '0')}-${selectedDay.toString().padStart(2, '0')}`;

            Swal.fire({
                title: 'Menyimpan Data...',
                text: 'Mohon tunggu sebentar',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => Swal.showLoading()
            });

            // ================= KBM =================
            let kbmData = [];
            document.querySelectorAll('.teacher-table tbody td[data-subject]').forEach(td => {
                const a = td.querySelector('a');
                const text = a.textContent.trim();

                // if (text.startsWith('[')) {
                //     const time = text.match(/\[(\d{2}:\d{2})\]/)?.[1];
                //     const note = text.replace(/^\[\d{2}:\d{2}\]\s*/, '');
                if (text && !text.includes('Klik untuk tambah')) {
                    // const time = text.match(/\[(\d{2}:\d{2})\]/)?.[1];
                    // const note = text.replace(/^\[\d{2}:\d{2}\]\s*/, '');
                    const time = '00:00'; // default time
                    const note = text;

                    kbmData.push({
                        subject: td.dataset.subject,
                        teacher_id: td.dataset.teacher,
                        date: dateStr,
                        time: '00:00', // default time
                        note
                    });
                }
            });

            // ================= ABSENSI =================
            let attendanceData = [];
            document.querySelectorAll('.editable-cell').forEach(cell => {
                const value = cell.textContent.trim().toUpperCase();
                if (['S', 'I', 'A'].includes(value)) {
                    attendanceData.push({
                        student_id: cell.dataset.student,
                        day: cell.dataset.day,
                        value
                    });
                }
            });

            if (kbmData.length === 0 && attendanceData.length === 0) {
                Swal.fire('Tidak ada perubahan', '', 'info');
                return;
            }

            try {
                const res = await fetch(`/journal/save-all?class=${className}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        month: selectedMonth,
                        year: selectedYear,
                        kbm: kbmData,
                        attendance: attendanceData
                    })
                });

                const data = await res.json();

                if (res.ok && data.success) {
                    skipUnloadWarning = true;
                    Swal.fire('Berhasil!', 'Data jurnal berhasil tersimpan.', 'success')
                        .then(() => location.reload());
                } else {
                    let errorMessage = data.message || 'Terjadi kesalahan saat menyimpan.';
                    if (data.errors) {
                        // Gabungkan semua pesan error validasi jika ada
                        errorMessage = Object.values(data.errors).flat().join('<br>');
                    }
                    Swal.fire('Gagal!', errorMessage, 'error');
                }

            } catch (err) {
                console.error(err);
                Swal.fire(
                    'Gagal!',
                    'Tidak bisa menyimpan data. Periksa koneksi internet atau hubungi admin.',
                    'error'
                );
            }
        });
    </script>
    <script>
        function buildKeterangan() {
            const flatpickrInput = document.getElementById('flatpickr-date');
            const selectedDay = flatpickrInput && flatpickrInput.value ? new Date(flatpickrInput.value).getDate() :
                new Date().getDate();
            const cells = document.querySelectorAll('.editable-cell');

            let list = {
                A: [],
                S: [],
                I: []
            };

            cells.forEach(cell => {
                if (parseInt(cell.dataset.day) !== selectedDay) return;

                const val = cell.textContent.trim().toUpperCase();
                if (['A', 'S', 'I'].includes(val)) {
                    const row = cell.closest('tr');
                    const nama = row.querySelector('td:nth-child(2)').textContent.trim();
                    list[val].push(nama);
                }
            });

            const container = document.getElementById('rekapKeterangan');

            if (!container) return;

            // Gabungkan semua tipe
            let all = [
                ...list.A.map(n => ({
                    n,
                    t: 'Alpha'
                })),
                ...list.S.map(n => ({
                    n,
                    t: 'Sakit'
                })),
                ...list.I.map(n => ({
                    n,
                    t: 'Izin'
                }))
            ];

            if (all.length === 0) {
                container.innerHTML =
                    `<em style="color: #64748b;"><i data-feather="check-circle" style="width: 16px; height: 16px; display: inline; vertical-align: -2px; margin-right: 4px;"></i>Semua siswa masuk</em>`;
                return;
            }

            const limit = 5;
            const visible = all.slice(0, limit);
            const hidden = all.slice(limit);

            let html =
                `<strong style="color: #166534;"><i data-feather="users" style="width: 16px; height: 16px; display: inline; vertical-align: -2px; margin-right: 4px;"></i>${all.length} siswa tidak masuk</strong><br><ol style="padding-left:18px; margin: 10px 0 0 0;">`;

            visible.forEach(x => {
                const iconName = x.t === 'Alpha' ? 'x-circle' : x.t === 'Sakit' ? 'alert-circle' : 'list';
                const iconHtml =
                    `<i data-feather="${iconName}" style="width: 14px; height: 14px; display: inline; vertical-align: -1px; margin-right: 4px;"></i>`;
                html +=
                    `<li style="margin-bottom: 6px;">${iconHtml}${x.n} <span style="color: #64748b; font-weight: 500;">(${x.t})</span></li>`;
            });

            html += `</ol>`;

            if (hidden.length > 0) {
                html += `
            <a href="#" id="readMoreAbsensi" class="text-primary" style="font-size:13px; color: #3b82f6; text-decoration: none; font-weight: 600;">
                <i data-feather="arrow-right" style="width: 14px; height: 14px; display: inline; vertical-align: -2px; margin-right: 2px;"></i>+${hidden.length} lainnya
            </a>
        `;

                setTimeout(() => {
                    document.getElementById('readMoreAbsensi').onclick = (e) => {
                        e.preventDefault();
                        showAllAbsensi(all);
                    };
                }, 0);
            }

            container.innerHTML = html;
        }

        function showAllAbsensi(data) {
            let html = `<ol style="text-align:left; padding-left:18px; margin: 0;">` +
                data.map(x => {
                    const iconName = x.t === 'Alpha' ? 'x-circle' : x.t === 'Sakit' ? 'alert-circle' : 'list';
                    const iconHtml =
                        `<i data-feather="${iconName}" style="width: 16px; height: 16px; display: inline; vertical-align: -2px; margin-right: 4px;"></i>`;
                    return `<li style="margin-bottom: 8px;">${iconHtml}<strong>${x.n}</strong> - <span style="color: #64748b;">${x.t}</span></li>`;
                }).join('') +
                `</ol>`;

            Swal.fire({
                title: '<i data-feather="users" style="width: 20px; height: 20px; display: inline; vertical-align: -3px; margin-right: 8px;"></i>Daftar Siswa Tidak Masuk',
                html: html,
                width: 500,
                confirmButtonText: '<i data-feather="check" style="width: 16px; height: 16px; display: inline; vertical-align: -2px; margin-right: 4px;"></i>Tutup',
                confirmButtonColor: '#3b82f6'
            });
        }

        // Auto jalankan saat load
        buildKeterangan();


        // Initialize Feather Icons
        if (typeof feather !== 'undefined') {
            feather.replace();
        }

        // Observe DOM changes and replace icons when new content is added
        const observer = new MutationObserver(() => {
            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true,
            innerHTML: true
        });

        // Also reinitialize when SweetAlert opens
        document.addEventListener('shown.bs.modal', () => {
            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        });
    </script>
@endsection
