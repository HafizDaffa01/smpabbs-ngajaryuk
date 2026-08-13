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

        /* ===== FILTER SECTION (Desktop first) ===== */
        .filter-section {
            display: flex;
            flex-wrap: wrap;
            gap: 1.25rem;
            align-items: center;
        }

        .filter-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: center;
        }

        .filter-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
        }

        .filter-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-left: auto;
        }

        /* ===== MOBILE: Filter section stacked ===== */
        @media (max-width: 768px) {
            .filter-section {
                flex-direction: column;
                align-items: stretch;
                gap: 0.75rem;
            }

            .filter-nav {
                flex-direction: column;
            }

            .filter-btn {
                flex: 1 1 100%;
                min-width: 100%;
            }

            .filter-actions {
                flex-direction: column;
                margin-left: 0;
            }

            .filter-actions .btn {
                width: 100%;
            }
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

        /* ===== COLLAPSIBLE SECTIONS (Mobile-First) ===== */
        .collapsible-section {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            margin-bottom: 1rem;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .collapsible-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.25rem;
            background: rgba(255, 255, 255, 0.03);
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
            user-select: none;
            transition: background 0.2s ease;
        }

        .collapsible-header:hover {
            background: rgba(255, 255, 255, 0.06);
        }

        .collapsible-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 700;
            font-size: 1rem;
            color: var(--text-main);
        }

        .collapsible-title i {
            font-size: 1.25rem;
            color: var(--primary-color);
        }

        .collapsible-badge {
            font-size: 0.7rem;
            font-weight: 800;
            padding: 0.25rem 0.6rem;
            border-radius: 999px;
            background: rgba(59, 130, 246, 0.2);
            color: var(--primary-color);
        }

        .collapsible-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            color: var(--text-muted);
            transition: all 0.2s ease;
        }

        .collapsible-toggle:hover {
            background: rgba(59, 130, 246, 0.15);
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .collapsible-toggle i {
            font-size: 1rem;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .collapsible-section.collapsed .collapsible-toggle i {
            transform: rotate(-90deg);
        }

        .collapsible-content {
            max-height: 5000px;
            opacity: 1;
            overflow: hidden;
            transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.2s ease, padding 0.3s ease;
        }

        .collapsible-section.collapsed .collapsible-content {
            max-height: 0;
            opacity: 0;
            padding: 0 !important;
        }

        .collapsible-body {
            padding: 1.25rem;
        }

        /* Section specific colors */
        .section-teachers .collapsible-title i { color: var(--info-color); }
        .section-teachers .collapsible-badge { background: rgba(14, 165, 233, 0.2); color: var(--info-color); }
        
        .section-attendance .collapsible-title i { color: var(--success-color); }
        .section-attendance .collapsible-badge { background: rgba(16, 185, 129, 0.2); color: var(--success-color); }
        
        .section-rekap .collapsible-title i { color: var(--warning-color); }
        .section-rekap .collapsible-badge { background: rgba(245, 158, 11, 0.2); color: var(--warning-color); }

        /* ===== Teacher/Subject Table ===== */
        .teacher-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.01);
        }

        .teacher-table th {
            background: rgba(255, 255, 255, 0.03);
            padding: 1rem 1rem;
            text-align: left;
            font-weight: 800;
            color: var(--text-muted);
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            border-bottom: 1px solid var(--border-color);
        }

        .teacher-table td {
            padding: 1.25rem 1rem;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-main);
            vertical-align: middle;
            transition: background 0.2s ease;
        }

        .teacher-table tbody tr:last-child td {
            border-bottom: none;
        }

        .teacher-table tbody tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }

        .teacher-subject {
            font-weight: 700;
            font-size: 1rem;
            color: var(--text-main);
        }

        .teacher-name {
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .kbm-link {
            text-decoration: none !important;
            font-weight: 600;
            font-size: 0.9rem;
            padding: 8px 14px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid transparent;
            display: inline-block;
            transition: all 0.2s ease;
            max-width: 100%;
            word-break: break-word;
        }

        .kbm-link:hover {
            background: rgba(59, 130, 246, 0.1);
            border-color: rgba(59, 130, 246, 0.2);
            color: var(--primary-color) !important;
        }

        .kbm-link.disabled {
            cursor: default;
            color: var(--text-muted) !important;
            background: transparent !important;
            border: none !important;
            padding: 0 !important;
        }

        /* ===== Attendance Table ===== */
        .table-wrapper {
            border-radius: 12px;
            border: 1px solid var(--border-color);
            overflow: auto;
            background: var(--bg-card);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .table {
            background: transparent !important;
            color: var(--text-main) !important;
            margin-bottom: 0;
            table-layout: fixed;
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
            position: sticky;
            top: 0;
            z-index: 20;
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
            min-width: 35px;
        }

        .col-nama {
            width: 160px;
            min-width: 140px;
            text-align: left !important;
            padding-left: 8px !important;
        }

        .col-status-sum {
            width: 30px;
            min-width: 30px;
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
            border-radius: 0 12px 12px 0;
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

        /* ===== EXPAND/COLLAPSE ALL BUTTONS ===== */
        .section-controls {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .section-controls .btn {
            font-size: 0.75rem;
            padding: 0.5rem 1rem;
        }

        /* ===== RESPONSIVE: Tablet (<= 1024px) ===== */
        @media (max-width: 1024px) {
            #journalContainer {
                padding: 1.5rem;
                margin: 1rem;
                border-radius: 12px;
            }

            .filter-section {
                gap: 1rem;
            }
        }

        /* ===== RESPONSIVE: Mobile (<= 768px) ===== */
        @media (max-width: 768px) {
            #journalContainer {
                padding: 1rem;
                margin: 0.5rem;
                border-radius: 8px;
            }

            /* Branding Header */
            .text-center.mb-5 h1 {
                font-size: 1.5rem !important;
                letter-spacing: 1px !important;
            }
            .text-center.mb-5 h2 {
                font-size: 1rem !important;
            }

            .page-header {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
                text-align: center;
                padding-bottom: 1rem;
            }

            .page-header h3 {
                font-size: 1.3rem;
                line-height: 1.4;
            }

            .page-header .back-link {
                justify-content: center;
            }

            .filter-section {
                flex-direction: column;
                align-items: stretch;
                padding: 1rem;
                gap: 0.75rem;
            }

            .filter-section > * {
                max-width: 100% !important;
                width: 100% !important;
            }

            .filter-section .btn {
                width: 100%;
                justify-content: center;
            }

            .filter-section .d-flex {
                flex-direction: column;
                width: 100%;
            }

            .filter-section .d-flex.gap-2 {
                flex-direction: row;
                flex-wrap: wrap;
            }

            .filter-section .d-flex.gap-2 .btn {
                flex: 1;
                min-width: 0;
            }

            .input-group {
                max-width: 100% !important;
            }

            .collapsible-header {
                padding: 0.875rem 1rem;
            }

            .collapsible-title {
                font-size: 0.95rem;
            }

            .collapsible-body {
                padding: 1rem;
            }

            .section-controls {
                justify-content: center;
            }

            .section-controls .btn {
                flex: 1;
                text-align: center;
            }

            .teacher-table th,
            .teacher-table td {
                padding: 0.75rem 0.5rem;
            }

            .teacher-subject {
                font-size: 0.9rem;
            }

            .teacher-name {
                font-size: 0.8rem;
            }

            .kbm-link {
                font-size: 0.8rem;
                padding: 8px 12px;
                min-height: 44px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            /* Tables for Mobile - Horizontal Scroll with Sticky Columns */
            .table-wrapper {
                margin: 0 -1rem 1rem -1rem;
                border-radius: 0;
                border-left: none;
                border-right: none;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                position: relative;
            }

            .table-wrapper::-webkit-scrollbar {
                height: 8px;
            }

            .table-wrapper::-webkit-scrollbar-track {
                background: var(--bg-body);
            }

            .table-wrapper::-webkit-scrollbar-thumb {
                background: var(--border-color);
                border-radius: 4px;
            }

            .table {
                min-width: 800px;
            }

            .teacher-table {
                min-width: 600px;
            }

            .col-nama {
                width: 150px;
                min-width: 140px;
            }

            /* Sticky columns for Attendance Table */
            .table thead tr:first-child th:nth-child(1),
            .table tbody tr td:nth-child(1) {
                position: sticky;
                left: 0;
                background: #1e293b !important;
                z-index: 11;
                box-shadow: 2px 0 8px rgba(0, 0, 0, 0.3);
            }

            /* Sticky Name Column */
            .table thead tr:first-child th.col-nama {
                position: sticky;
                left: 40px;
                background: #1e293b !important;
                z-index: 10;
                box-shadow: 2px 0 8px rgba(0, 0, 0, 0.2);
            }

            /* Sticky header rows */
            .table thead tr {
                position: sticky;
                top: 0;
                z-index: 20;
            }

            .table thead tr:first-child {
                z-index: 21;
            }

            /* Improve cell touch targets */
            .editable-cell {
                padding: 8px 2px;
            }

            .editable-cell span {
                font-size: 0.9rem;
            }

            .col-nama {
                position: sticky;
                left: 40px;
                background: #1e293b !important;
                z-index: 10;
                box-shadow: 2px 0 8px rgba(0, 0, 0, 0.2);
                white-space: nowrap;
                font-size: 0.85rem;
                max-width: 160px;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .col-no {
                width: 40px;
                min-width: 40px;
            }

            .col-status-sum {
                width: 36px;
                min-width: 36px;
            }

            /* Attendance summary cells */
            .bg-soft-success,
            .bg-soft-warning,
            .bg-soft-danger {
                font-size: 0.85rem;
            }

            #rekapKeterangan {
                padding: 1rem;
                font-size: 0.85rem;
                border-radius: 0 0 12px 12px;
                border-left: none;
                border-top: 4px solid #10b981;
            }

            /* Search input full width */
            #searchStudent {
                width: 100%;
            }

            /* Date picker button full width */
            #btnCalendar {
                width: 100%;
            }
            }

            /* Search input full width */
            #searchStudent {
                width: 100%;
            }
        }

        /* ===== RESPONSIVE: Small Mobile (<= 480px) ===== */
        @media (max-width: 480px) {
            #journalContainer {
                padding: 0.75rem 0.5rem;
                margin: 0.25rem;
                border-radius: 6px;
            }

            .text-center.mb-5 {
                margin-bottom: 2rem !important;
            }

            .text-center.mb-5 h1 {
                font-size: 1.25rem !important;
            }

            .text-center.mb-5 h2 {
                font-size: 0.9rem !important;
            }

            .page-header h3 {
                font-size: 1.1rem;
            }

            .page-header p {
                font-size: 0.85rem !important;
            }

            .badge {
                padding: 0.35rem 0.5rem;
                font-size: 0.7rem;
            }

            .collapsible-header {
                padding: 0.75rem 0.75rem;
            }

            .collapsible-title {
                font-size: 0.9rem;
            }

            .collapsible-body {
                padding: 0.75rem;
            }

            .collapsible-badge {
                font-size: 0.7rem !important;
                padding: 0.25rem 0.5rem !important;
            }

            .teacher-table th,
            .teacher-table td {
                padding: 0.6rem 0.4rem;
            }

            .teacher-subject {
                font-size: 0.85rem;
            }

            .teacher-name {
                font-size: 0.75rem;
            }

            .kbm-link {
                font-size: 0.75rem;
                padding: 6px 8px;
            }

            .col-nama {
                width: 130px;
                min-width: 120px;
            }

            .col-no {
                width: 36px;
                min-width: 36px;
            }

            .col-status-sum {
                width: 32px;
                min-width: 32px;
                font-size: 0.75rem;
            }

            .editable-cell span {
                font-size: 0.85rem;
            }

            #rekapKeterangan {
                padding: 0.75rem;
                font-size: 0.8rem;
            }

            .section-controls .btn {
                font-size: 0.7rem;
                padding: 0.4rem 0.75rem;
            }

            .filter-btn {
                font-size: 0.8rem;
                padding: 0.5rem 0.75rem;
            }

            .page-header .back-link {
                font-size: 0.8rem;
                padding: 4px 10px;
            }
        }

        /* ===== RESPONSIVE: Extra Small Mobile (<= 360px) ===== */
        @media (max-width: 360px) {
            #journalContainer {
                padding: 0.5rem 0.25rem;
            }

            .page-header h3 {
                font-size: 1rem;
            }

            .collapsible-header {
                padding: 0.6rem 0.6rem;
            }

            .collapsible-title {
                font-size: 0.85rem;
            }

            .collapsible-body {
                padding: 0.6rem;
            }

            .teacher-subject {
                font-size: 0.8rem;
            }

            .teacher-name {
                font-size: 0.7rem;
            }
        }

        /* Print styles - always expanded */
        @media print {
            .collapsible-section {
                break-inside: avoid;
            }
            .collapsible-section.collapsed .collapsible-content {
                max-height: 5000px;
                opacity: 1;
            }
            .collapsible-toggle {
                display: none;
            }
            .section-controls {
                display: none;
            }
        }
    </style>

    <div class="container-fluid" id="journalContainer">
        
        <!-- Branding Header -->
        <div class="text-center mb-5">
            <h1 class="h2 fw-900 text-white mb-1 text-spacing-wide">JOURNAL OF SUBJECT</h1>
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
                <p class="mb-0 mt-2 text-muted fw-600 d-flex align-items-center gap-2">
                    <i data-feather="calendar" class="icon-sm"></i>
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

            <div class="filter-nav">
                @php
                    $currentDate = \Carbon\Carbon::create($year, $month, $day);
                    $prevDate = (clone $currentDate)->subDay();
                    $nextDate = (clone $currentDate)->addDay();
                @endphp

                <a href="{{ route('journal.show', ['class' => $grade, 'day' => $prevDate->day, 'month' => $prevDate->month, 'year' => $prevDate->year, 'usr' => $usr]) }}"
                    class="btn btn-outline-primary btn-sm filter-btn px-3 py-2 btn-touch">
                    <i class="fas fa-angle-double-left me-2"></i><span class="d-none d-sm-inline">Tanggal Sebelumnya</span><span class="d-sm-none">Sebelum</span>
                </a>

                <a href="{{ route('journal.show', ['class' => $grade, 'day' => $nextDate->day, 'month' => $nextDate->month, 'year' => $nextDate->year, 'usr' => $usr]) }}"
                    class="btn btn-outline-primary btn-sm filter-btn px-3 py-2 btn-touch">
                    <span class="d-none d-sm-inline">Tanggal Selanjutnya</span><span class="d-sm-none">Selanjut</span><i class="fas fa-angle-double-right ms-2"></i>
                </a>

                <div class="position-relative filter-btn btn-touch">
                    <button type="button" id="btnCalendar" class="btn btn-outline-primary w-100 h-100 px-3 py-2 text-truncate text-nowrap">
                        <i class="fas fa-calendar-alt me-2"></i>{{ $currentDate->translatedFormat('d M Y') }}
                    </button>
                    <input type="text" id="flatpickr-date" value="{{ sprintf('%04d-%02d-%02d', $year, $month, $day) }}"
                        class="flatpickr-hidden">
                </div>
            </div>

            <div class="filter-actions">
                <button id="btnSave" class="btn btn-primary w-100 btn-touch"><i data-feather="save" class="me-1"></i><span class="d-none d-sm-inline">Simpan</span></button>

                @if (Auth::user()->is_admin)
                    <a href="{{ route('journal.export', [
                        'class' => $grade,
                        'month' => $month,
                        'year' => $year,
                        'day' => $day,
                    ]) }}"
                        class="btn btn-info w-100 btn-touch">
                        <i data-feather="download" class="me-1"></i><span class="d-none d-sm-inline">Export Excel</span>
                    </a>
                @endif
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
            {{-- ================= SECTION CONTROLS ================= --}}
            <div class="section-controls no-print">
                <button type="button" class="btn btn-outline-primary btn-sm" id="expandAll">
                    <i data-feather="maximize-2" class="me-1"></i> Buka Semua
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="collapseAll">
                    <i data-feather="minimize-2" class="me-1"></i> Tutup Semua
                </button>
            </div>

            {{-- =================== SECTION 1: MAPEL / GURU =================== --}}
            <div class="collapsible-section section-teachers" data-section="teachers">
                <div class="collapsible-header" data-toggle="teachers">
                    <div class="collapsible-title">
                        <i data-feather="book-open"></i>
                        <span>Mata Pelajaran & Guru</span>
                    </div>
                    <span class="collapsible-badge">{{ $teachers->count() }} Mapel</span>
                    <div class="collapsible-toggle" aria-label="Toggle section">
                        <i data-feather="chevron-down"></i>
                    </div>
                </div>
                <div class="collapsible-content">
                    <div class="collapsible-body">
                    <div class="table-wrapper mb-0">
                        <table class="teacher-table journal-table">
                            <thead>
                                <tr>
                                    <th class="col-mapel">Mapel / Mata Pelajaran</th>
                                    <th class="col-guru">Nama Guru</th>
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
                                            <td>
                                                <div class="teacher-subject">{{ $sub }}</div>
                                            </td>
                                            <td>
                                                <div class="teacher-name">{{ $t->name }}</div>
                                            </td>

                                            {{-- ====== KBM ====== --}}
                                            <td @if ($canEditKbm) onclick="addKeterangan(this)" @endif
                                                data-subject="{{ $sub }}" data-teacher="{{ $t->id }}">

                                                <div class="d-flex align-items-center justify-content-between gap-2">
                                                    @if ($canEditKbm)
                                                        <a href="#" role="button" tabindex="0" class="kbm-link note-link"
                                                            onclick="addKeterangan(this.closest('td')); event.preventDefault();">
                                                            @if ($note)
                                                                {!! nl2br(e($note->note)) !!}
                                                            @else
                                                                 <i data-feather="edit-2" class="feather-16" style="vertical-align: -2px;"></i> Klik untuk tambah keterangan
                                                            @endif
                                                        </a>
                                                    @else
                                                        <div class="kbm-link disabled note-link">
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
                                                <td class="text-break w-300"
                                                    rowspan="{{ count($teachers) }}" id="rekapKeterangan">
                                                    <em class="text-muted">Tidak ada siswa absen</em>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- =================== SECTION 2: ABSENSI =================== --}}
            <div class="collapsible-section section-attendance" data-section="attendance">
                <div class="collapsible-header" data-toggle="attendance">
                    <div class="collapsible-title">
                        <i data-feather="users"></i>
                        <span>Absensi Siswa</span>
                    </div>
                    <span class="collapsible-badge">{{ $students->count() }} Siswa</span>
                    <div class="collapsible-toggle" aria-label="Toggle section">
                        <i data-feather="chevron-down"></i>
                    </div>
                </div>
                <div class="collapsible-content">
                    <div class="collapsible-body">
                        <div class="input-group me-auto mb-3 max-w-300">
                            <span class="input-group-text bg-transparent border-end-0 text-muted">
                                <i data-feather="search" class="feather-16"></i>
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
                                        <th colspan="31" id="tgl" class="note-display">
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
                                            <th class="day-col-width"
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
                                                        <span class="cell-attendance">{{ $attendance[$student->id][$i] ?? '' }}</span>
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
                    </div>
                </div>
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
                            `<span class="cell-attendance">${select.value}</span>`;
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
                            `<span class="cell-attendance">${select.value}</span>`;
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
        function addKeterangan(ts) {

            const p = ts.querySelector("a");
            const isPlaceholder = p.textContent.includes("Klik untuk tambah");

            // Simpan isi lama untuk restore ketika Cancel
            const originalText = p.textContent;
            const originalColor = p.style.color;

            // Kalau masih placeholder -> kosongkan dulu
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
                        // Jika user hapus isinya -> tampilkan placeholder lagi
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
            document.queryAll('.teacher-table tbody td[data-subject]').forEach(td => {
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
    <script>
        // ===== COLLAPSIBLE SECTIONS LOGIC =====
        document.addEventListener('DOMContentLoaded', function() {
            const sections = document.querySelectorAll('.collapsible-section');
            
            // Load saved state from localStorage (only for non-admin users)
            const isTeacher = {{ !Auth::user()->is_admin ? 'true' : 'false' }};
            if (isTeacher) {
                sections.forEach(section => {
                    const sectionName = section.dataset.section;
                    const savedState = localStorage.getItem(`journal_section_${sectionName}`);
                    if (savedState === 'collapsed') {
                        section.classList.add('collapsed');
                    }
                });
            }

            // Toggle function
            function toggleSection(header) {
                const section = header.closest('.collapsible-section');
                if (!section) return;
                
                const isCollapsed = section.classList.toggle('collapsed');
                const sectionName = section.dataset.section;
                
                if (isTeacher) {
                    localStorage.setItem(`journal_section_${sectionName}`, isCollapsed ? 'collapsed' : 'expanded');
                }
                
                // Update feather icons
                if (typeof feather !== 'undefined') {
                    feather.replace();
                }
            }

            // Click handlers for headers
            document.querySelectorAll('.collapsible-header').forEach(header => {
                header.addEventListener('click', function(e) {
                    // Don't toggle if clicking on a link/button inside
                    if (e.target.closest('a, button, .kbm-link')) return;
                    toggleSection(this);
                });
            });

            // Expand All button
            document.getElementById('expandAll')?.addEventListener('click', function() {
                sections.forEach(section => {
                    section.classList.remove('collapsed');
                    if (isTeacher) {
                        localStorage.setItem(`journal_section_${section.dataset.section}`, 'expanded');
                    }
                });
                if (typeof feather !== 'undefined') feather.replace();
            });

            // Collapse All button
            document.getElementById('collapseAll')?.addEventListener('click', function() {
                sections.forEach(section => {
                    section.classList.add('collapsed');
                    if (isTeacher) {
                        localStorage.setItem(`journal_section_${section.dataset.section}`, 'collapsed');
                    }
                });
                if (typeof feather !== 'undefined') feather.replace();
            });

            // Keyboard accessibility
            document.querySelectorAll('.collapsible-header').forEach(header => {
                header.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        toggleSection(this);
                    }
                });
                header.setAttribute('tabindex', '0');
                header.setAttribute('role', 'button');
            });
        });
    </script>
@endsection