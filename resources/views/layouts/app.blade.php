<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    <title>@yield('title', config('app.name'))</title>

    <!-- CDN Libraries -->
    @include('layouts.cdn')

    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])



    <style>
        :root {
            --primary-color: #3b82f6;
            --primary-hover: #2563eb;
            --secondary-color: #94a3b8;
            --success-color: #10b981;
            --info-color: #0ea5e9;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --bg-body: #0f172a;
            --bg-card: #1e293b;
            --bg-navbar: #1e293b;
            --border-color: #334155;
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -1px rgba(0, 0, 0, 0.2);
            --card-radius: 12px;

            /* Aliases for better compatibility */
            --accent-blue: var(--primary-color);
            --accent-green: var(--success-color);
            --accent-info: var(--info-color);
            --accent-warning: var(--warning-color);
            --text-dark: #f8fafc;
        }

        /* Semantic Soft Backgrounds adjusted for Dark Mode */
        .bg-soft-primary {
            background-color: rgba(59, 130, 246, 0.15) !important;
            color: #60a5fa !important;
        }

        .bg-soft-success {
            background-color: rgba(16, 185, 129, 0.15) !important;
            color: #34d399 !important;
        }

        .bg-soft-info {
            background-color: rgba(14, 165, 233, 0.15) !important;
            color: #38bdf8 !important;
        }

        .bg-soft-warning {
            background-color: rgba(245, 158, 11, 0.15) !important;
            color: #fbbf24 !important;
        }

        .bg-soft-danger {
            background-color: rgba(239, 68, 68, 0.15) !important;
            color: #f87171 !important;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
        }

        .navbar {
            background-color: var(--bg-navbar) !important;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border-color);
        }

        .navbar-brand {
            font-weight: 800;
            color: var(--primary-color) !important;
            letter-spacing: -0.5px;
            font-size: 1.25rem;
        }

        .nav-link {
            font-weight: 600;
            color: var(--text-muted) !important;
            padding: 0.5rem 1rem !important;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .nav-link:hover {
            color: var(--primary-color) !important;
            background-color: rgba(59, 130, 246, 0.1);
        }

        .navbar-toggler-icon {
            filter: brightness(100) invert(0);
            /* Ensure pure white */
        }

        /* Only show X icon when the toggler is NOT collapsed (meaning it's open) */
        .navbar-toggler[aria-expanded="true"] .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23ffffff'%3e%3cpath d='M.293.293a1 1 0 011.414 0L8 6.586 14.293.293a1 1 0 111.414 1.414L9.414 8l6.293 6.293a1 1 0 01-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 01-1.414-1.414L6.586 8 .293 1.707a1 1 0 010-1.414z'/%3e%3c/svg%3e") !important;
            filter: none !important;
        }

        .nav-link.active {
            color: var(--primary-color) !important;
            background-color: rgba(59, 130, 246, 0.15);
        }

        .card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--card-radius);
            box-shadow: var(--card-shadow);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .card-header {
            background-color: rgba(255, 255, 255, 0.03);
            border-bottom: 1px solid var(--border-color);
            padding: 1.25rem;
            border-radius: var(--card-radius) var(--card-radius) 0 0 !important;
        }

        .btn {
            border-radius: 8px;
            padding: 0.6rem 1.25rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
            transform: translateY(-1px);
        }

        .form-control,
        .form-select {
            background-color: #0f172a;
            color: var(--text-main);
            border-radius: 8px;
            padding: 0.6rem 1rem;
            border: 1.5px solid var(--border-color);
        }

        .form-control:focus,
        .form-select:focus {
            background-color: #0f172a;
            color: var(--text-main);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        .badge {
            padding: 0.5em 0.75em;
            border-radius: 6px;
            font-weight: 600;
        }

        /* Utils */
        .text-primary-bold {
            color: var(--primary-color);
            font-weight: 700;
        }

        .rounded-xl {
            border-radius: 1rem !important;
        }

        /* Mobile Bottom Nav */
        .mobile-bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 70px;
            background: var(--bg-navbar);
            box-shadow: 0 -5px 20px rgba(0, 0, 0, 0.3);
            z-index: 1050;
            border-top: 1px solid var(--border-color);
            display: flex;
            align-items: center;
        }

        .mobile-nav-link {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.7rem;
            font-weight: 700;
            gap: 4px;
            transition: all 0.2s ease;
            flex: 1;
        }

        .mobile-nav-link i {
            font-size: 1.25rem;
            transition: transform 0.2s ease;
        }

        .mobile-nav-link.active {
            color: var(--primary-color);
        }

        .mobile-nav-link.active i {
            transform: translateY(-2px);
        }

        /* Dropdown refinements */
        .dropdown-menu {
            margin-top: 0.5rem !important;
            padding: 0.5rem;
            background-color: var(--bg-card) !important;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4) !important;
            border: 1px solid var(--border-color) !important;
            z-index: 2000;
        }

        .dropdown-item {
            border-radius: 8px;
            color: var(--text-muted) !important;
            font-weight: 600;
            padding: 0.6rem 1rem !important;
            transition: all 0.2s ease;
        }

        .dropdown-item:hover {
            background-color: rgba(255, 255, 255, 0.05) !important;
            color: var(--primary-color) !important;
        }

        .dropdown-item.text-danger:hover {
            background-color: rgba(239, 68, 68, 0.1) !important;
            color: #f87171 !important;
        }

        @media (max-width: 767.98px) {
            body.has-bottom-nav {
                padding-bottom: 80px;
            }

            body.has-bottom-nav .navbar-toggler {
                display: none;
                /* Hide toggle only for users with bottom nav */
            }

            .navbar-brand {
                margin-left: auto;
                margin-right: auto;
            }
        }

        /* Prevent Dropdown Clipping */
        .navbar-collapse {
            overflow: visible !important;
        }

        .dropdown-menu {
            display: none;
            min-width: 200px;
            padding: 0.5rem;
            background-color: var(--bg-card) !important;
            border: 1px solid var(--border-color) !important;
            border-radius: var(--card-radius);
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.5) !important;
            margin-top: 0.5rem !important;
        }

        .dropdown-menu.show {
            display: block !important;
            animation: dropdownFadeIn 0.2s ease-out;
        }

        @keyframes dropdownFadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            width: 100%;
            padding: 0.7rem 1rem !important;
            clear: both;
            font-weight: 600;
            color: var(--text-muted) !important;
            text-align: inherit;
            text-decoration: none;
            white-space: nowrap;
            background-color: transparent;
            border: 0;
            border-radius: 8px;
            transition: all 0.2s ease;
            gap: 10px;
        }

        .dropdown-item:hover {
            background-color: rgba(255, 255, 255, 0.05) !important;
            color: var(--primary-color) !important;
        }

        .dropdown-item i {
            font-size: 1rem;
        }

        /* Dark Mode SweetAlert2 overrides */
        .swal2-popup {
            background-color: var(--bg-card) !important;
            color: var(--text-main) !important;
            border: 1px solid var(--border-color) !important;
            box-shadow: 0 10px 40px -10px rgba(0, 0, 0, 0.7) !important;
            border-radius: 16px !important;
        }

        .swal2-title {
            color: var(--text-main) !important;
        }

        .swal2-content,
        .swal2-html-container {
            color: var(--text-muted) !important;
        }

        .swal2-confirm.btn-danger {
            background-color: var(--danger-color) !important;
            border-color: var(--danger-color) !important;
            box-shadow: none !important;
        }

        .swal2-cancel.btn-secondary {
            background-color: transparent !important;
            border-color: var(--secondary-color) !important;
            color: var(--secondary-color) !important;
            box-shadow: none !important;
        }

        .swal2-cancel.btn-secondary:hover {
            color: var(--text-main) !important;
            background-color: rgba(148, 163, 184, 0.1) !important;
        }

        .swal2-icon.swal2-warning {
            border-color: var(--warning-color) !important;
            color: var(--warning-color) !important;
        }

        .swal2-icon.swal2-error {
            border-color: var(--danger-color) !important;
            color: var(--danger-color) !important;
        }

        .swal2-icon.swal2-success {
            border-color: var(--success-color) !important;
            color: var(--success-color) !important;
        }

        width: 20px;
        text-align: center;
        }

        .dropdown-item.text-danger:hover {
            background-color: rgba(239, 68, 68, 0.1) !important;
            color: #f87171 !important;
        }
    </style>

</head>

<body class="@auth @if (!Auth::user()->is_admin) has-bottom-nav @endif @endauth">
    @php
        // ====== LOGIKA PENENTUAN PERIODE OTOMATIS ======
        $months = [
            'jan_feb' => [
                'label' => 'Januari 21 - Februari 20',
                'start' => ['month' => 1, 'day' => 21],
                'end' => ['month' => 2, 'day' => 20],
            ],
            'feb_mar' => [
                'label' => 'Februari 21 - Maret 20',
                'start' => ['month' => 2, 'day' => 21],
                'end' => ['month' => 3, 'day' => 20],
            ],
            'mar_apr' => [
                'label' => 'Maret 21 - April 20',
                'start' => ['month' => 3, 'day' => 21],
                'end' => ['month' => 4, 'day' => 20],
            ],
            'apr_mei' => [
                'label' => 'April 21 - Mei 20',
                'start' => ['month' => 4, 'day' => 21],
                'end' => ['month' => 5, 'day' => 20],
            ],
            'mei_jun' => [
                'label' => 'Mei 21 - Juni 20',
                'start' => ['month' => 5, 'day' => 21],
                'end' => ['month' => 6, 'day' => 20],
            ],
            'jun_jul' => [
                'label' => 'Juni 21 - Juli 20',
                'start' => ['month' => 6, 'day' => 21],
                'end' => ['month' => 7, 'day' => 20],
            ],
            'jul_agu' => [
                'label' => 'Juli 21 - Agustus 20',
                'start' => ['month' => 7, 'day' => 21],
                'end' => ['month' => 8, 'day' => 20],
            ],
            'agu_sep' => [
                'label' => 'Agustus 21 - September 20',
                'start' => ['month' => 8, 'day' => 21],
                'end' => ['month' => 9, 'day' => 20],
            ],
            'sep_okt' => [
                'label' => 'September 21 - Oktober 20',
                'start' => ['month' => 9, 'day' => 21],
                'end' => ['month' => 10, 'day' => 20],
            ],
            'okt_nov' => [
                'label' => 'Oktober 21 - November 20',
                'start' => ['month' => 10, 'day' => 21],
                'end' => ['month' => 11, 'day' => 20],
            ],
            'nov_des' => [
                'label' => 'November 21 - Desember 20',
                'start' => ['month' => 11, 'day' => 21],
                'end' => ['month' => 12, 'day' => 20],
            ],
            'des_jan' => [
                'label' => 'Desember 21 - Januari 20',
                'start' => ['month' => 12, 'day' => 21],
                'end' => ['month' => 1, 'day' => 20],
            ],
        ];

        $today = new DateTime();
        $currentKey = null;

        foreach ($months as $key => $range) {
            $startMonth = $range['start']['month'];
            $startDay = $range['start']['day'];
            $endMonth = $range['end']['month'];
            $endDay = $range['end']['day'];

            $year = (int) $today->format('Y');
            $startDate = new DateTime("$year-$startMonth-$startDay");

            // Jika periode menyeberang tahun (contohnya Des-Jan)
            $endYear = $endMonth < $startMonth ? $year + 1 : $year;
            $endDate = new DateTime("$endYear-$endMonth-$endDay");

            if ($today >= $startDate && $today <= $endDate) {
                $currentKey = $key;
                break;
            }
        }

        // Jika tidak ketemu (fallback)
        if (!$currentKey) {
            $currentKey = 'jan_feb';
        }
    @endphp

    <div id="app">
        <nav class="navbar navbar-expand-md sticky-top navbar-dark">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center" href="{{ url('/') }}">
                    <i class="fas fa-journal-whills me-2"></i>
                    {{ config('app.name') }}
                </a>
                <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side -->
                    <ul class="navbar-nav me-auto ps-lg-4">
                        @auth
                            @if (!Auth::user()->is_admin)
                                <li class="nav-item">
                                    <a class="nav-link {{ Request::is('absensi*') ? 'active' : '' }}"
                                        href="{{ url('/absensi') }}">
                                        <i class="fas fa-check-square me-1"></i> Absensi
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ Request::is('journal*') ? 'active' : '' }}"
                                        href="{{ url('/journal?usr=' . Auth::user()->id) }}">
                                        <i class="fas fa-book me-1"></i> Jurnal
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ Request::is('prevSmes*') ? 'active' : '' }}"
                                        href="{{ url('/prevSmes?usr=' . Auth::user()->id) }}">
                                        <i class="fas fa-history me-1"></i> Rekap
                                    </a>
                                </li>
                            @endif
                        @endauth

                        @auth
                            @if (Auth::user()->is_admin)
                                <li class="nav-item">
                                    <a class="nav-link {{ Request::is('admin') ? 'active' : '' }}"
                                        href="{{ url('/admin') }}">
                                        <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ Request::is('admin/ts*') ? 'active' : '' }}"
                                        href="{{ url('/admin/ts') }}">
                                        <i class="fas fa-users-cog me-1"></i> TS Manager
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ Request::is('journal*') ? 'active' : '' }}"
                                        href="{{ url('/journal?usr=' . Auth::user()->id) }}">
                                        <i class="fas fa-book me-1"></i> Jurnal
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ Request::is('prevSmes*') ? 'active' : '' }}"
                                        href="{{ url('/prevSmes?usr=' . Auth::user()->id) }}">
                                        <i class="fas fa-book me-1"></i> Rekap Semester
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ Request::is('schedule*') ? 'active' : '' }}"
                                        href="{{ url('/schedule') }}">
                                        <i class="fas fa-calendar-alt me-1"></i> Jadwal Editor
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ Request::is('backup*') ? 'active' : '' }}"
                                        href="{{ url('/backup?month=' . $currentKey) }}">
                                        <i class="fas fa-database me-1"></i> Backup
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ Request::is('explorer*') ? 'active' : '' }}"
                                        href="{{ url('/explorer') }}">
                                        <i class="fas fa-folder-open me-1"></i> Explorer
                                    </a>
                                </li>
                            @endif
                        @endauth
                    </ul>

                    <!-- Right Side -->
                    <ul class="navbar-nav ms-auto">
                        @guest
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('login') }}">Login</a>
                            </li>
                        @else
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle d-flex align-items-center"
                                    href="javascript:void(0)" role="button"
                                    onclick="const m = this.nextElementSibling; const isS = m.classList.contains('show'); document.querySelectorAll('.dropdown-menu.show').forEach(el => el.classList.remove('show')); if(!isS) m.classList.add('show'); event.stopPropagation();">
                                    <div class="bg-soft-primary rounded-circle d-flex align-items-center justify-content-center me-2"
                                        style="width: 32px; height: 32px;">
                                        <i class="fas fa-user text-primary" style="font-size: 0.8rem;"></i>
                                    </div>
                                    <span class="fw-bold">{{ Auth::user()->name }}</span>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <div class="px-3 py-2 mb-1 border-bottom d-md-none">
                                        <div class="fw-bold text-muted small">Signed in as</div>
                                        <div class="text-muted small truncate">{{ Auth::user()->email }}</div>
                                    </div>
                                    <a class="dropdown-item py-2" href="javascript:void(0)" onclick="openProfileModal()">
                                        <i class="fas fa-user-edit text-primary"></i> Edit Profil
                                    </a>
                                    <a class="dropdown-item py-2 text-danger" href="{{ route('logout') }}"
                                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                        <i class="fas fa-sign-out-alt"></i> Logout
                                    </a>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
            @csrf
        </form>

        <main class="py-5" style="background-color: var(--bg-body); min-height: 100vh;">
            @yield('content')
        </main>
    </div>

    @auth
    <script>
        function openProfileModal() {
            Swal.fire({
                title: 'Edit Profil Saya',
                html: `
                    <div class="text-start py-2">
                        <label class="form-label small fw-bold">NAMA:</label>
                        <input type="text" id="profName" class="swal2-input m-0 w-100" value="{{ Auth::user()->name }}">
                    </div>
                    <div class="text-start py-2">
                        <label class="form-label small fw-bold">NOMOR HP:</label>
                        <input type="text" id="profPhone" class="swal2-input m-0 w-100" value="{{ Auth::user()->phone_num }}" placeholder="08xxxxxxxx">
                    </div>
                    <div class="text-start py-2">
                        <label class="form-label small fw-bold">PASSWORD BARU (KOSONGKAN JIKA TIDAK DIUBAH):</label>
                        <input type="password" id="profPassword" class="swal2-input m-0 w-100" placeholder="Minimal 6 karakter">
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Simpan',
                cancelButtonText: 'Batal',
                focusConfirm: false,
                preConfirm: () => {
                    const name = document.getElementById('profName').value;
                    const phone_num = document.getElementById('profPhone').value;
                    const password = document.getElementById('profPassword').value;

                    if (!name) {
                        Swal.showValidationMessage('Nama wajib diisi');
                        return false;
                    }

                    return { name, phone_num, password };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Menyimpan...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });

                    fetch("{{ route('profile.update') }}", {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(result.value)
                    })
                    .then(r => r.json())
                    .then(d => {
                        if (d.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: d.message,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => location.reload());
                        } else {
                            Swal.fire('Gagal', d.message || 'Terjadi kesalahan', 'error');
                        }
                    })
                    .catch(e => {
                        Swal.fire('Error', 'Terjadi kesalahan sistem', 'error');
                    });
                }
            });
        }
    </script>
    @endauth
    @auth
        @if (!Auth::user()->is_admin)
            <div class="mobile-bottom-nav d-md-none">
                <a href="{{ url('/absensi') }}" class="mobile-nav-link {{ Request::is('absensi*') ? 'active' : '' }}">
                    <i class="fas fa-calendar-check"></i>
                    <span>Absensi</span>
                </a>
                <a href="{{ url('/journal?usr=' . Auth::user()->id) }}"
                    class="mobile-nav-link {{ Request::is('journal*') ? 'active' : '' }}">
                    <i class="fas fa-book-open"></i>
                    <span>Jurnal</span>
                </a>
                <a href="{{ url('/prevSmes?usr=' . Auth::user()->id) }}"
                    class="mobile-nav-link {{ Request::is('prevSmes*') ? 'active' : '' }}">
                    <i class="fas fa-history"></i>
                    <span>Rekap</span>
                </a>
                <a href="{{ route('logout') }}" class="mobile-nav-link"
                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Keluar</span>
                </a>
            </div>
        @endif
    @endauth

    @stack('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof feather !== 'undefined') {
                feather.replace();
            }

            // Handle Navbar & Dropdown closing
            document.addEventListener('click', function(e) {
                const navContent = document.getElementById('navbarSupportedContent');

                // Dropdown handling
                if (!e.target.closest('.dropdown')) {
                    document.querySelectorAll('.dropdown-menu.show').forEach(m => m.classList.remove(
                        'show'));
                }

                // Navbar Collapse handling
                if (navContent && navContent.classList.contains('show') && !e.target.closest('.navbar')) {
                    const bsCollapse = bootstrap.Collapse.getInstance(navContent) || new bootstrap.Collapse(
                        navContent, {
                            toggle: false
                        });
                    bsCollapse.hide();
                }
            });

            // Close navbar when clicking links (Mobile)
            document.querySelectorAll('.navbar-nav .nav-link').forEach(link => {
                link.addEventListener('click', function() {
                    const navContent = document.getElementById('navbarSupportedContent');
                    if (navContent && navContent.classList.contains('show')) {
                        const bsCollapse = bootstrap.Collapse.getInstance(navContent) ||
                            new bootstrap.Collapse(navContent, {
                                toggle: false
                            });
                        bsCollapse.hide();
                    }
                });
            });
        });
    </script>
</body>

</html>
