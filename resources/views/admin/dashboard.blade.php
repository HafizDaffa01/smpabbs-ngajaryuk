@extends('layouts.app')

@section('title', 'Admin Panel')

@section('content')

<div class="container pb-5">
    <!-- Header -->
    <div class="dashboard-header animate__animated animate__fadeIn">
        <div class="row align-items-center">
            <div class="col-md-7">
                <h2 class="mb-1" id="greeting">Selamat datang!</h2>
                <p class="mb-0">Kelola data absensi, jurnal, dan jadwal dalam satu panel kontrol pusat.
                </p>
            </div>
            <div class="col-md-5 text-md-end mt-4 mt-md-0">
                <div class="time-info">
                    <div id="clock">--:--:--</div>
                    <div id="date" class="fw-700">--</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4 g-4">
        <div class="col-md-6">
            <button class="card stats-card bg-primary-light w-100 text-start border-0" onclick="showTeachersPopup()" aria-label="Detail Total Guru">
                <div class="card-body">
                    <div class="stats-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h5>Total Guru</h5>
                    <h2>{{ $totalTeachers }}</h2>
                    <p class="text-primary-bold mb-0">
                        <i class="fas fa-touch-pointer me-1"></i> Ketuk untuk detail
                    </p>
                </div>
            </button>
        </div>
        <div class="col-md-6">
            <button class="card stats-card bg-success-light w-100 text-start border-0" onclick="showAbsenciPopup()" aria-label="Detail Total Absensi">
                <div class="card-body">
                    @php $today = new DateTime(); @endphp
                    <div class="stats-icon">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <h5>Total Absensi</h5>
                    <h2>{{ $absensiCount }}</h2>
                    <p class="text-success mb-0">
                        <i class="fas fa-touch-pointer me-1"></i> Ketuk untuk detail
                    </p>
                </div>
            </button>
        </div>
    </div>

    <!-- Classes and Students Counter -->
    <div class="row mb-4 g-4">
        <div class="col-lg-6 col-md-6 col-12 mb-3">
            <button class="card stats-card bg-info-light w-100 text-start border-0" onclick="showClassesPopup()" aria-label="Detail Total Kelas">
                <div class="card-body">
                    <div class="stats-icon">
                        <i class="fas fa-school"></i>
                    </div>
                    <h5>Total Kelas</h5>
                    <h2>{{ $totalClasses }}</h2>
                    <p class="text-info mb-0">
                        <i class="fas fa-touch-pointer me-1"></i> Ketuk untuk detail
                    </p>
                </div>
            </button>
        </div>
        <div class="col-lg-6 col-md-6 col-12 mb-3">
            <button class="card stats-card bg-warning-light w-100 text-start border-0" onclick="showStudentsPopup()" aria-label="Detail Total Siswa">
                <div class="card-body">
                    <div class="stats-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <h5>Total Siswa</h5>
                    <h2>{{ $totalStudents }}</h2>
                    <p class="text-warning mb-0">
                        <i class="fas fa-touch-pointer me-1"></i> Ketuk untuk detail
                    </p>
                </div>
            </button>
        </div>
    </div>

    <!-- Users Section -->
    <div class="users-section">
            <div class="section-title-wrapper">
                <div class="section-title-icon">
                    <i class="fas fa-users-cog"></i>
                </div>
                <h2 class="section-title">Teachers Management</h2>
            </div>
            <div class="mb-3">
                <input type="text" id="searchUsers" class="form-control search-box"
                    placeholder="Search by name or email...">
            </div>

            <div id="usersTableWrapper" class="table-responsive-wrapper">
                <table class="table table-hover" id="usersTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $teachers = $users->where('is_admin', 0); @endphp
                        @if ($teachers->count() > 0)
                            @foreach ($teachers as $i => $user)
                                <tr>
                                    <td><strong>{{ $loop->iteration }}</strong></td>
                                    <td><strong>{{ $user->name }}</strong></td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        <form class="delete-user-form" action="{{ route('admin.deleteUser', $user->id) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-danger btn-sm" type="submit" title="Delete User">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="4">
                                    <div class="empty-state">
                                        <i class="fas fa-inbox"></i>
                                        <p>Belum ada guru terdaftar</p>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Jam + Tanggal realtime
        function updateClock() {
            const now = new Date();

            // Format jam HH:MM:SS
            const timeOptions = {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };

            // Menggunakan replace untuk memastikan separator adalah titik dua (:) 
            // karena locale id-ID seringkali menggunakan titik (.)
            document.getElementById('clock').innerText = now.toLocaleTimeString('id-ID', timeOptions).replace(/\./g, ':');

            // Format tanggal lengkap (Hari, Tanggal Bulan Tahun)
            const dateOptions = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            };
            document.getElementById('date').innerText = now.toLocaleDateString('id-ID', dateOptions);
        }

        // Greeting otomatis
        function updateGreeting() {
            const now = new Date();
            const hour = now.getHours();
            let greeting;

            if (hour >= 4 && hour < 11) {
                greeting = "Selamat pagi";
            } else if (hour >= 11 && hour < 15) {
                greeting = "Selamat siang";
            } else if (hour >= 15 && hour < 18) {
                greeting = "Selamat sore";
            } else {
                greeting = "Selamat malam";
            }

            document.getElementById('greeting').innerText = `${greeting}, Admin!`;
        }

        // Get responsive width for mobile
        function getPopupWidth() {
            if (window.innerWidth <= 480) {
                return '95vw';
            } else if (window.innerWidth <= 768) {
                return '90vw';
            } else {
                return '700px';
            }
        }

    // Popup untuk menampilkan daftar guru
        function showTeachersPopup() {
            const teachersData = @json($users);
            const totalTeachers = teachersData.length;

            let teacherContent = `
                <div class="popup-list">
                    <h4 class="text-primary-bold"><i data-feather="users" class="feather-20 me-2"></i>Daftar Guru (Total: ${totalTeachers})</h4>
                    <ul>
            `;

            teachersData.forEach((teacher, index) => {
                teacherContent += `
                    <li class="text-main">
                        <span class="text-primary-bold">${index + 1}.</span>
                        ${teacher.name}
                    </li>
                `;
            });

            teacherContent += `
                    </ul>
                </div>
            `;

            Swal.fire({
                title: '<i data-feather="users" class="feather-24 me-2"></i>Daftar Guru',
                html: teacherContent,
                icon: 'info',
                confirmButtonText: 'Tutup',
                confirmButtonColor: 'var(--accent-blue)',
                width: getPopupWidth(),
                padding: '20px',
                scrollbarPadding: false,
                didOpen: (modal) => {
                    const title = modal.querySelector('.swal2-title');
                    if (title) {
                        title.style.fontSize = window.innerWidth <= 480 ? '1.2rem' : '1.5rem';
                    }
                    feather.replace();
                }
            });
        }

        // Popup untuk menampilkan absensi hari ini
        function showAbsenciPopup() {
            const today = new Date();
            const dateOptions = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            };
            const todayDate = today.toLocaleDateString('id-ID', dateOptions);
            const absenciCount = {{ $absensiCount }};
            const currentKey = '{{ $currentKey }}';

            let html = `
                <div class="popup-detail">
                    <div class="mb-3">
                        <p class="text-muted">Tanggal:</p>
                        <h3 class="text-success">${todayDate}</h3>
                    </div>
                    <div class="popup-detail-box">
                        <p class="text-muted">Total Absensi</p>
                        <h2 class="text-success">${absenciCount}</h2>
                    </div>
                    <a href="/backup?month=${currentKey}" class="btn btn-success popup-link">
                        <i class="fas fa-chart-bar"></i> Lihat Backup Panel
                    </a>
                </div>
            `;

            Swal.fire({
                title: '<i data-feather="bar-chart-2" class="feather-24 me-2"></i>Absensi',
                html: html,
                icon: 'info',
                confirmButtonText: 'Tutup',
                confirmButtonColor: 'var(--accent-green)',
                width: getPopupWidth(),
                padding: '20px',
                scrollbarPadding: false,
                didOpen: (modal) => {
                    const title = modal.querySelector('.swal2-title');
                    if (title) {
                        title.style.fontSize = window.innerWidth <= 480 ? '1.2rem' : '1.5rem';
                    }
                    feather.replace();
                }
            });
        }

        // Popup untuk menampilkan daftar kelas
        function showClassesPopup() {
            const classesData = @json($studentsByClass);
            const filteredClasses = Object.fromEntries(
                Object.entries(classesData).filter(([key]) => key !== 'Kelas')
            );
            const totalClasses = Object.keys(filteredClasses).length;

            let classContent = `
                <div class="popup-list">
                    <h4 class="text-info"><i data-feather="book-open" class="feather-20 me-2"></i>Daftar Kelas (Total: ${totalClasses})</h4>
            `;

            for (const [className, students] of Object.entries(filteredClasses)) {
                classContent += `
                    <div class="popup-item">
                        <strong class="text-info"><i data-feather="home" class="feather-18 me-1-5"></i>${className}</strong>
                        <p class="text-muted">${students.length} siswa</p>
                    </div>
                `;
            }

            classContent += `</div>`;

            Swal.fire({
                title: '<i data-feather="bar-chart-2" class="feather-24 me-2"></i>Ringkasan Kelas',
                html: classContent,
                icon: 'info',
                confirmButtonText: 'Tutup',
                confirmButtonColor: 'var(--accent-info)',
                width: getPopupWidth(),
                padding: '20px',
                scrollbarPadding: false,
                didOpen: (modal) => {
                    const title = modal.querySelector('.swal2-title');
                    if (title) {
                        title.style.fontSize = window.innerWidth <= 480 ? '1.2rem' : '1.5rem';
                    }
                    feather.replace();
                }
            });
        }

        // Popup untuk menampilkan detail siswa per kelas
        function showStudentsPopup() {
            const classesData = @json($studentsByClass);
            const filteredClasses = Object.fromEntries(
                Object.entries(classesData).filter(([key]) => key !== 'Kelas')
            );
            const totalStudents = Object.values(filteredClasses).flat().filter(s => s.name !== 'Nama').length;

            let html = `
                <div class="popup-list">
                    <h4 class="text-warning"><i data-feather="users" class="feather-20 me-2"></i>Daftar Siswa (Total: ${totalStudents})</h4>
            `;

            for (const [className, students] of Object.entries(filteredClasses)) {
                const filteredStudents = students.filter(s => s.name !== 'Nama');
                html += `
                    <div class="popup-item popup-item-warning">
                        <strong class="text-warning"><i data-feather="clipboard" class="feather-18 me-1-5"></i>Kelas ${className}</strong>
                        <p class="text-muted">Siswa (${filteredStudents.length}):</p>
                        <ul>
                `;

                filteredStudents.forEach((student, index) => {
                    html += `
                        <li class="text-main">
                            <span class="text-warning">●</span>
                            ${student.name}
                        </li>
                    `;
                });

                html += `
                        </ul>
                    </div>
                `;
            }

            html += `</div>`;

            Swal.fire({
                title: '<i data-feather="book-open" class="feather-24 me-2"></i>Detail Siswa Per Kelas',
                html: html,
                icon: 'info',
                confirmButtonText: 'Tutup',
                confirmButtonColor: 'var(--accent-warning)',
                width: getPopupWidth(),
                padding: '20px',
                scrollbarPadding: false,
                didOpen: (modal) => {
                    const title = modal.querySelector('.swal2-title');
                    if (title) {
                        title.style.fontSize = window.innerWidth <= 480 ? '1.2rem' : '1.5rem';
                    }
                    feather.replace();
                }
            });
        }

        setInterval(updateClock, 1000);
        updateClock();
        updateGreeting();

        document.querySelectorAll('.delete-user-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: 'Hapus Guru?',
                    text: 'Tindakan ini tidak dapat dibatalkan.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Hapus',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#64748b'
                }).then(result => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>

@endsection
