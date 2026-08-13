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



</head>

<body class="@auth @if (!Auth::user()->is_admin) user-layout @endif @endauth">
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
        <nav class="navbar navbar-expand-lg sticky-top navbar-dark">
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
                            @endif
                        @endauth

                        @auth
                            @if (Auth::user()->is_admin)
                                <li class="nav-item">
                                    <a class="nav-link {{ Request::is('admin/import') ? 'active' : '' }}"
                                        href="{{ url('/admin/import') }}">
                                        <i class="fas fa-file-import me-1"></i> Import
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ Request::is('/') ? 'active' : '' }}"
                                        href="{{ url('/') }}">
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
                                    href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <div class="nav-user-avatar me-2">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <span class="fw-bold">{{ Auth::user()->name }}</span>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <div class="px-3 py-2 mb-1 border-bottom d-md-none">
                                        <div class="fw-bold text-muted small">Signed in as</div>
                                        <div class="text-muted small truncate">{{ Auth::user()->email }}</div>
                                    </div>
                                    <a class="dropdown-item py-2" href="{{ route('profile.edit') }}">
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

        <main id="main-content" class="flex-fill">
            @yield('content')
        </main>

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
                    <a href="{{ route('profile.edit') }}"
                        class="mobile-nav-link {{ Request::is('profile*') ? 'active' : '' }}">
                        <i class="fas fa-user-circle"></i>
                        <span>Profil</span>
                    </a>
                </div>
            @endif
        @endauth
    </div>

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
