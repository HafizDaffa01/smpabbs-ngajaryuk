@extends('layouts.app')

@section('title', 'TS Manager')

@section('content')

<div class="container pb-5">
        <div class="d-flex align-items-center mb-4">
            <h1 class="h3 fw-800 m-0 text-uppercase tracking-tight">
                <i class="fas fa-users-cog me-2"></i>TS Manager
            </h1>
        </div>

        {{-- TAB NAVIGATION --}}
        <div class="tab-header">
            <button class="tab-button active" data-tab="students-tab" data-param="S">
                <i class="fas fa-user-graduate me-2"></i>Student Manager
            </button>
            <button class="tab-button" data-tab="teachers-tab" data-param="T">
                <i class="fas fa-chalkboard-teacher me-2"></i>Teacher Manager
            </button>
        </div>

        {{-- STUDENT TAB --}}
        <div id="students-tab" class="tab-content active">
            <div class="panel card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h2 class="m-0">Student Manager</h2>
                    <div>
                        <button id="saveStudentChanges" class="btn btn-success">Save Changes</button>
                        <span id="pendingCount" class="badge bg-soft-warning text-warning ms-2 d-none">0</span>
                        <button class="btn btn-danger ms-2" onclick="deleteStudentsByClass()">
                            Hapus Siswa (Per Kelas)
                        </button>
                    </div>
                </div>

                @php
                    $allStudents = \App\Models\Student::all();
                    $kelasList = \App\Models\Student::select('grade')->distinct()->orderBy('grade')->pluck('grade');
                @endphp

                <input type="text" id="searchMurid" placeholder="Cari Murid..." class="form-control mb-2">

                <select id="studentKelasSelect" class="form-control mb-3">
                    <option value="" selected disabled>-- Pilih Kelas --</option>
                    @foreach (['7A', '7B', '7C', '7D', '7E', '7F', '8A', '8B', '8C', '8D', '8E', '8F', '9A', '9B', '9C', '9D', '9E', '9F'] as $k)
                        <option value="{{ $k }}">{{ $k }}</option>
                    @endforeach
                </select>

                <div id="studentList" class="student-list-container">
                    <p class="text-muted">Daftar siswa akan muncul di sini...</p>
                </div>
            </div>

            <hr class="section-divider">

            <div class="row g-4">
                <div class="col-md-7">
                    <div class="panel card h-100">
                        <h5 class="section-title mt-0">Tambah Siswa Baru</h5>
                        <form id="addStudentForm">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-uppercase">Nama Siswa:</label>
                                <input type="text" name="name" class="form-control" placeholder="Nama Siswa" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-uppercase">Pilih Kelas:</label>
                                <select name="grade" class="form-control" required>
                                    <option value="" disabled selected>-- Pilih Kelas --</option>
                                    @foreach (['7A', '7B', '7C', '7D', '7E', '7F', '8A', '8B', '8C', '8D', '8E', '8F', '9A', '9B', '9C', '9D', '9E', '9F'] as $k)
                                        <option value="{{ $k }}">{{ $k }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Tambah</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- TEACHER TAB --}}
        <div id="teachers-tab" class="tab-content">
            <div class="panel card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h2 class="m-0">Teacher Manager</h2>
                    <button class="btn btn-danger btn-sm" onclick="deleteAllTeachers()">
                        Hapus Semua Guru
                    </button>
                </div>

                <input type="text" id="searchTeacher" placeholder="Cari guru..." class="form-control mb-3">

                <div id="tableContainer">
                    <p>Memuat data guru...</p>
                </div>
            </div>

            <hr class="section-divider">

            <div class="row g-4">
                <div class="col-md-7">
                    <div class="panel card h-100">
                        <h5 class="section-title mt-0">Tambah Guru Baru</h5>
                        <form id="addTeacherForm">
                            @csrf
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label small fw-bold text-uppercase">Nama Guru:</label>
                                    <input type="text" name="name" class="form-control" required
                                        placeholder="Nama Guru">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label small fw-bold text-uppercase">Email:</label>
                                    <input type="email" name="email" class="form-control" required
                                        placeholder="Email">
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label small fw-bold text-uppercase">Nomor HP:</label>
                                    <input type="text" name="phone_num" class="form-control"
                                        placeholder="Nomor HP (Contoh: 08123456789)">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-uppercase">Password:</label>
                                <div class="pw-wrapper">
                                    <input type="password" name="password" id="inputPw" class="form-control" required
                                        placeholder="Password" minlength="6">
                                    <i data-feather="eye" class="text-white" id="pwEye" onclick="showPw()"></i>
                                </div>
                            </div>

                            <hr class="section-divider my-3">
                            <h4 class="h6 text-primary-bold fw-800 text-uppercase mb-3">Manajemen Mapel</h4>

                            <div id="mapelContainer">
                                <div class="mapel-row d-flex gap-2 align-items-center">
                                    <select name="kelas[]" class="form-control mapel-select-w">
                                        <option value="" selected disabled>-- Kelas --</option>
                                        @foreach (['7', '8', '9', '7A', '7B', '7C', '7D', '7E', '7F', '8A', '8B', '8C', '8D', '8E', '8F', '9A', '9B', '9C', '9D', '9E', '9F', 'KEPSEK'] as $k)
                                            <option value="{{ $k }}">{{ $k }}</option>
                                        @endforeach
                                    </select>
                                    <select name="mapel[]" class="form-control mapel-type-w">
                                        <option value="" selected disabled>-- Mapel --</option>
                                        @foreach (['IFE', 'Mathematics', 'Science', 'Social', 'Civics', 'English', 'Indonesian', 'SPORT', 'Quran', 'TKA Mathematics', 'TKA INDO', 'ICT', 'Leadership'] as $m)
                                            <option value="{{ $m }}">{{ $m }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-danger btn-sm"
                                        onclick="removeRow(this)">X</button>
                                </div>
                            </div>

                            <div class="d-flex gap-2 mt-3">
                                <button type="button" class="btn btn-secondary btn-sm" onclick="addMapelRow()">+ Tambah
                                    Mapel</button>
                                <button type="submit" class="btn btn-primary btn-sm px-4">Simpan Guru</button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        // ==========================================
        //  GLOBAL / UTILITY
        // ==========================================
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof feather !== 'undefined') feather.replace();
        });

        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000,
        });

        let pwToggle = false;

        function showPw() {
            pwToggle = !pwToggle;
            const pw = document.getElementById('inputPw');
            const icon = document.getElementById('pwEye');
            if (pw) pw.type = pwToggle ? "text" : "password";
            if (icon) icon.setAttribute("data-feather", pwToggle ? "eye-off" : "eye");
            if (typeof feather !== 'undefined') feather.replace();
        }

        // ==========================================
        //  TAB SYSTEM
        // ==========================================
        function activateTab(tabParam) {
            let targetButton = null;
            document.querySelectorAll('.tab-button').forEach(btn => {
                if (btn.dataset.param === tabParam) targetButton = btn;
            });

            if (!targetButton) targetButton = document.querySelector('.tab-button[data-param="S"]');
            if (!targetButton) return;

            const tabName = targetButton.dataset.tab;
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));

            targetButton.classList.add('active');
            const tabEl = document.getElementById(tabName);
            if (tabEl) tabEl.classList.add('active');

            if (typeof feather !== 'undefined') feather.replace();
        }

        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', function() {
                const param = this.dataset.param;
                const url = new URL(window.location.href);
                url.searchParams.set('tab', param);
                window.history.replaceState({}, '', url);
                activateTab(param);
            });
        });

        // ==========================================
        //  STUDENT MANAGER
        // ==========================================
        const allStudents = @json($allStudents);
        const kelasList = @json($kelasList);
        const studentList = document.getElementById('studentList');
        const studentKelasSelect = document.getElementById('studentKelasSelect');
        let filteredStudents = [];
        let pendingChanges = [];

        window.addEventListener('beforeunload', function(e) {
            if (pendingChanges.length > 0) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        function markChange(select) {
            const studentId = select.dataset.id;
            const newGrade = select.value;
            const oldGrade = select.dataset.old;

            if (!studentId || newGrade === oldGrade) {
                pendingChanges = pendingChanges.filter(p => p.id != studentId);
                select.style.border = "";
                updatePendingCount();
                return;
            }

            const idx = pendingChanges.findIndex(p => p.id == studentId);
            if (idx >= 0) pendingChanges[idx].grade = newGrade;
            else pendingChanges.push({
                id: studentId,
                grade: newGrade
            });

            select.style.border = "2px solid orange";
            updatePendingCount();
        }

        function updatePendingCount() {
            const el = document.getElementById('pendingCount');
            if (!el) return;
            if (pendingChanges.length === 0) {
                el.style.display = 'none';
                el.textContent = '0';
            } else {
                el.style.display = '';
                el.textContent = pendingChanges.length;
            }
        }

        const saveStudentChangesBtn = document.getElementById('saveStudentChanges');
        if (saveStudentChangesBtn) {
            saveStudentChangesBtn.addEventListener('click', function() {
                if (pendingChanges.length === 0) {
                    Toast.fire({
                        icon: 'info',
                        title: 'Tidak ada perubahan'
                    });
                    return;
                }

                Swal.fire({
                    title: 'Simpan perubahan?',
                    text: `Ada ${pendingChanges.length} perubahan`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Simpan',
                    cancelButtonText: 'Batal'
                }).then(result => {
                    if (!result.isConfirmed) return;
                    Swal.fire({
                        title: 'Menyimpan...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });

                    const promises = pendingChanges.map(p =>
                        fetch(`/admin/student/${p.id}/update-grade`, {
                            method: 'PUT',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                grade: p.grade
                            })
                        }).then(r => r.json())
                    );

                    Promise.all(promises).then(() => {
                        Swal.close();
                        Toast.fire({
                            icon: 'success',
                            title: 'Perubahan berhasil disimpan'
                        });
                        pendingChanges = [];
                        updatePendingCount();
                        setTimeout(() => location.reload(), 700);
                    });
                });
            });
        }

        function filterStudents(kelas) {
            filteredStudents = allStudents.filter(s => s.grade === kelas);
            renderStudents(filteredStudents);
        }

        if (studentKelasSelect) {
            studentKelasSelect.addEventListener('change', function() {
                const url = new URL(window.location.href);
                url.searchParams.set('Kls', this.value);
                window.history.replaceState({}, '', url);
                filterStudents(this.value);
            });
        }

        function renderStudents(list) {
            if (list.length === 0) {
                studentList.innerHTML = `
                    <div class="text-center py-5 text-muted opacity-75">
                        <i class="fas fa-user-slash mb-3 icon-xl"></i>
                        <p>Belum ada data siswa di kelas ini</p>
                    </div>
                `;
                return;
            }

            let html = `<table class="ts-table">
                <thead><tr><th>No</th><th>Nama</th><th>Kelas</th><th>Aksi</th></tr></thead><tbody>`;

            list.forEach((s, i) => {
                let options = '';
                for (let g = 7; g <= 9; g++) {
                    ['A', 'B', 'C', 'D', 'E', 'F'].forEach(c => {
                        let k = g + c;
                        options += `<option value="${k}" ${k === s.grade ? 'selected' : ''}>${k}</option>`;
                    });
                }

                html += `<tr>
                    <td>${i+1}</td>
                    <td class="fw-bold">${s.name}</td>
                    <td>
                        <select class="form-select form-select-sm" data-id="${s.id}" data-old="${s.grade}" onchange="markChange(this)">
                            ${options}
                        </select>
                    </td>
                    <td>
                        <button class="btn btn-danger btn-sm" onclick="deleteStudent(${s.id})">Hapus</button>
                    </td>
                </tr>`;
            });
            html += `</tbody></table>`;
            studentList.innerHTML = html;
        }

        function deleteStudent(id) {
            Swal.fire({
                title: 'Hapus siswa ini?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Hapus',
            }).then(result => {
                if (result.isConfirmed) {
                    fetch(`/admin/student/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    }).then(() => {
                        Toast.fire({
                            icon: 'success',
                            title: 'Siswa dihapus'
                        });
                        setTimeout(() => location.reload(), 700);
                    });
                }
            });
        }

        const searchMuridInput = document.getElementById('searchMurid');
        if (searchMuridInput) {
            searchMuridInput.addEventListener('input', function() {
                const key = this.value.toLowerCase();
                renderStudents(filteredStudents.filter(s => s.name.toLowerCase().includes(key)));
            });
        }

        function deleteStudentsByClass() {
            let html = `
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="selectAllKelas">
                    <label class="form-check-label fw-bold" for="selectAllKelas">Pilih Semua Kelas</label>
                </div>
                <hr>
                <div class="kelas-delete-container">`;

            const validKelas = kelasList.filter(k => /^[789][A-F]$/.test(k));
            validKelas.forEach(k => {
                html += `
                    <div class="form-check">
                        <input class="form-check-input del-kelas" type="checkbox" value="${k}" id="kelas-${k}">
                        <label class="form-check-label" for="kelas-${k}">Kelas ${k}</label>
                    </div>`;
            });
            html += `</div>`;

            Swal.fire({
                title: 'Hapus Siswa Berdasarkan Kelas',
                html,
                icon: 'warning',
                showCancelButton: true,
                didOpen: () => {
                    const selectAll = document.getElementById('selectAllKelas');
                    const items = document.querySelectorAll('.del-kelas');
                    if (selectAll) {
                        selectAll.addEventListener('change', function() {
                            items.forEach(cb => cb.checked = this.checked);
                        });
                    }
                },
                preConfirm: () => {
                    const selected = [...document.querySelectorAll('.del-kelas:checked')].map(el => el.value);
                    if (selected.length === 0) {
                        Swal.showValidationMessage('Pilih minimal satu kelas');
                        return false;
                    }
                    return selected;
                }
            }).then(res => {
                if (!res.isConfirmed) return;
                const selectedClasses = res.value;
                const studentsToDelete = allStudents.filter(s => selectedClasses.includes(s.grade));

                if (studentsToDelete.length === 0) {
                    Toast.fire({
                        icon: 'info',
                        title: 'Tidak ada siswa di kelas tersebut'
                    });
                    return;
                }

                const captcha = Math.floor(1000 + Math.random() * 9000);
                Swal.fire({
                    title: 'Konfirmasi',
                    icon: 'warning',
                    showCancelButton: true,
                    html: `
                        <p class="text-danger fw-bold">Anda akan menghapus ${studentsToDelete.length} siswa.</p>
                        <p>Masukkan kode berikut: </p>
                        <h2 class="captcha-display">${captcha}</h2>
                        <input id="captchaInput" type="number" class="swal2-input">`,
                    preConfirm: () => {
                        const input = document.getElementById('captchaInput').value;
                        if (parseInt(input) !== captcha) {
                            Swal.showValidationMessage('Captcha salah');
                            return false;
                        }
                        return true;
                    }
                }).then(result => {
                    if (!result.isConfirmed) return;
                    Swal.fire({
                        title: 'Menghapus...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });
                    deleteRecursive(studentsToDelete.map(s => s.id), 0, '/admin/student/');
                });
            });
        }

        // ==========================================
        //  TEACHER MANAGER
        // ==========================================
        function loadTable() {
            const tableContainer = document.getElementById('tableContainer');
            if (!tableContainer) return;
            fetch('/admin/teacher/table')
                .then(r => r.text())
                .then(html => {
                    tableContainer.innerHTML = html;
                    if (typeof feather !== 'undefined') feather.replace();
                });
        }

        function initTeacherFeatures() {
            loadTable();
            const searchTeacher = document.getElementById('searchTeacher');
            if (searchTeacher) {
                searchTeacher.addEventListener('input', function() {
                    const key = this.value.toLowerCase();
                    document.querySelectorAll("#tableContainer tbody tr").forEach(r => {
                        r.style.display = r.innerText.toLowerCase().includes(key) ? "" : "none";
                    });
                });
            }

            const addTeacherForm = document.getElementById('addTeacherForm');
            if (addTeacherForm) {
                addTeacherForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Menambah...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });
                    fetch('/admin/add-teacher', {
                        method: 'POST',
                        headers: {
                            "X-CSRF-TOKEN": "{{ csrf_token() }}",
                            "Accept": "application/json"
                        },
                        body: new FormData(this)
                    }).then(r => r.json()).then(d => {
                        Swal.close();
                        if (d.status === 'success') {
                            Toast.fire({
                                icon: 'success',
                                title: 'Guru ditambahkan'
                            });
                            loadTable();
                            this.reset();
                        } else if (d.errors) {
                            // Laravel validation errors
                            const firstError = Object.values(d.errors)[0][0];
                            Swal.fire("Error", firstError, "error");
                        } else {
                            Swal.fire("Error", d.message || 'Gagal menambahkan guru', "error");
                        }
                    });
                });
            }

        }

        function deleteUser(id) {
            Swal.fire({
                title: 'Hapus guru?',
                icon: 'warning',
                showCancelButton: true
            }).then(res => {
                if (res.isConfirmed) {
                    fetch(`/admin/user/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        })
                        .then(() => {
                            Toast.fire({
                                icon: 'success',
                                title: 'Berhasil'
                            });
                            loadTable();
                        });
                }
            });
        }

        function editTeacher(id, name, email, phone) {
            Swal.fire({
                title: 'Edit Profil Guru',
                html: `
                    <div class="text-left py-2">
                        <label class="form-label small fw-bold">NAMA GURU:</label>
                        <input type="text" id="editName" class="swal2-input m-0 w-100" value="${name}">
                    </div>
                    <div class="text-left py-2">
                        <label class="form-label small fw-bold">EMAIL:</label>
                        <input type="email" id="editEmail" class="swal2-input m-0 w-100" value="${email}">
                    </div>
                    <div class="text-left py-2">
                        <label class="form-label small fw-bold">NOMOR HP:</label>
                        <input type="text" id="editPhone" class="swal2-input m-0 w-100" value="${phone === 'null' ? '' : phone}" placeholder="08xxxxxxxx">
                    </div>
                    <div class="text-left py-2">
                        <label class="form-label small fw-bold">PASSWORD BARU (KOSONGKAN JIKA TIDAK DIUBAH):</label>
                        <input type="password" id="editPassword" class="swal2-input m-0 w-100" placeholder="Kosongkan jika tidak diubah">
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Simpan Perubahan',
                cancelButtonText: 'Batal',
                focusConfirm: false,
                preConfirm: () => {
                    const name = document.getElementById('editName').value;
                    const email = document.getElementById('editEmail').value;
                    const phone_num = document.getElementById('editPhone').value;
                    const password = document.getElementById('editPassword').value;

                    if (!name || !email) {
                        Swal.showValidationMessage('Nama dan Email wajib diisi');
                        return false;
                    }

                    return { name, email, phone_num, password };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Menyimpan...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });

                    fetch(`/admin/teacher/${id}`, {
                        method: 'PUT',
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
                            Toast.fire({
                                icon: 'success',
                                title: d.message
                            });
                            loadTable();
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

        function deleteAllTeachers() {
            const rows = document.querySelectorAll('#tableContainer tbody tr');
            const ids = Array.from(rows).map(r => r.dataset.id).filter(Boolean);
            if (ids.length === 0) return;
            Swal.fire({
                title: 'Hapus semua guru?',
                icon: 'warning',
                showCancelButton: true
            }).then(res => {
                if (res.isConfirmed) {
                    Swal.fire({
                        title: 'Menghapus...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });
                    deleteRecursive(ids, 0, '/admin/user/', 5);
                }
            });
        }

        // ==========================================
        //  MAPEL MANAGEMENT
        // ==========================================
        function addMapelRow() {
            const container = document.getElementById('mapelContainer');
            if (!container) return;
            const div = document.createElement('div');
            div.className = 'mapel-row d-flex gap-2 align-items-center mb-2';
            div.innerHTML = `
                <select name="kelas[]" class="form-control mapel-select-w">
                    <option value="" selected disabled>-- Kelas --</option>
                    ${['7','8','9','7A','7B','7C','7D','7E','7F','8A','8B','8C','8D','8E','8F','9A','9B','9C','9D','9E','9F','KEPSEK'].map(k => `<option value="${k}">${k}</option>`).join('')}
                </select>
                <select name="mapel[]" class="form-control mapel-type-w">
                    <option value="" selected disabled>-- Mapel --</option>
                    ${['IFE','Mathematics','Science','Social','Civics','English','Indonesian','SPORT','Quran','TKA Mathematics','TKA INDO','ICT','Leadership'].map(m => `<option value="${m}">${m}</option>`).join('')}
                </select>
                <button type="button" class="btn btn-danger btn-sm" onclick="this.parentElement.remove()">X</button>
            `;
            container.appendChild(div);
        }

        function removeRow(btn) {
            btn.parentElement.remove();
        }

        function editMapel(userId, mapelData, guruName = '') {
            Swal.fire({
                title: `Edit Mapel - ${guruName}`,
                width: 700,
                showCancelButton: true,
                confirmButtonText: 'Simpan',
                html: `<div id="mapelEditContainer"></div><button type="button" class="btn btn-secondary btn-sm mt-2" onclick="addEditMapelRow()">+ Tambah</button>`,
                didOpen: () => {
                    if (!mapelData || (Array.isArray(mapelData) ? mapelData.length === 0 : Object.keys(
                            mapelData).length === 0)) {
                        addEditMapelRow();
                    } else if (Array.isArray(mapelData)) {
                        mapelData.forEach(item => {
                            // Cek jika item adalah object {kelas, mapel}
                            if (typeof item === 'object' && item !== null && item.kelas) {
                                addEditMapelRow(item.kelas, item.mapel);
                            } else {
                                // Fallback jika formatnya aneh
                                addEditMapelRow('', item);
                            }
                        });
                    } else {
                        // Format Object: { "7A": ["MTK", "IPA"], "8B": "B.Indo" }
                        Object.entries(mapelData).forEach(([k, m]) => {
                            if (Array.isArray(m)) {
                                m.forEach(singleM => addEditMapelRow(k, singleM));
                            } else {
                                addEditMapelRow(k, m);
                            }
                        });
                    }
                },
                preConfirm: () => {
                    const res = [];
                    const rows = document.querySelectorAll('.edit-mapel-row');
                    rows.forEach(row => {
                        const k = row.querySelector('.edit-kelas').value;
                        const m = row.querySelector('.edit-mapel-type').value;
                        if (k && m) res.push({
                            kelas: k,
                            mapel: m
                        });
                    });
                    if (res.length === 0) {
                        Swal.showValidationMessage('Isi minimal satu');
                        return false;
                    }
                    return res;
                }
            }).then(r => {
                if (!r.isConfirmed) return;
                fetch(`/admin/teacher/${userId}/mapel`, {
                    method: 'PUT',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        mapel: r.value
                    })
                }).then(res => res.json()).then(d => {
                    if (d.status === 'success') {
                        Toast.fire({
                            icon: 'success',
                            title: 'Berhasil'
                        });
                        loadTable();
                    }
                });
            });
        }

        function addEditMapelRow(k = '', m = '') {
            const container = document.getElementById('mapelEditContainer');
            const kelasOpts = ['7', '8', '9', '7A', '7B', '7C', '7D', '7E', '7F', '8A', '8B', '8C', '8D', '8E', '8F', '9A',
                '9B', '9C', '9D', '9E', '9F', 'KEPSEK'
            ];
            const mapelOpts = ['IFE', 'Mathematics', 'Science', 'Social', 'Civics', 'English', 'Indonesian', 'SPORT',
                'Quran', 'TKA Mathematics', 'TKA INDO', 'Leadership', 'ICT'
            ];

            const div = document.createElement('div');
            div.className = 'edit-mapel-row d-flex gap-2 align-items-center mb-2';
            div.innerHTML = `
                <select class="form-control edit-kelas mapel-select-w">
                    <option value="" disabled ${!k?'selected':''}>Kelas</option>
                    ${kelasOpts.map(opt => `<option value="${opt}" ${opt.toUpperCase() === (k||'').toString().toUpperCase() ? 'selected' : ''}>${opt}</option>`).join('')}
                </select>
                <select class="form-control edit-mapel-type mapel-type-w">
                    <option value="" disabled ${!m?'selected':''}>Mapel</option>
                    ${mapelOpts.map(opt => `<option value="${opt}" ${opt.toLowerCase() === (m||'').toString().toLowerCase() ? 'selected' : ''}>${opt}</option>`).join('')}
                </select>
                <button type="button" class="btn btn-danger btn-sm" onclick="this.parentElement.remove()">X</button>
            `;
            container.appendChild(div);
        }

        // ==========================================
        //  RECURSIVE DELETE
        // ==========================================
        async function deleteRecursive(ids, index, endpoint, batchSize = 5) {
            const total = ids.length;

            for (let i = index; i < total; i += batchSize) {
                const currentBatch = ids.slice(i, i + batchSize);
                const promises = currentBatch.map(id => fetch(endpoint + id, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                }).catch(e => console.error("Error deleting ID " + id, e)));

                // Tunggu batch ini selesai (asynchronous execution)
                await Promise.all(promises);

                const container = Swal.getHtmlContainer();
                if (container) container.innerHTML = `${Math.min(i + batchSize, total)} / ${total}`;
            }

            // Setelah semua data berhasil melalui loop asinkronus:
            Swal.close();
            Toast.fire({
                icon: 'success',
                title: 'Berhasil dihapus'
            });

            if (endpoint.includes('user')) loadTable();
            else setTimeout(() => location.reload(), 700);
        }

        // ==========================================
        //  ENTRY POINT
        // ==========================================
        document.addEventListener('DOMContentLoaded', () => {
            const params = new URLSearchParams(window.location.search);
            activateTab(params.get('tab') || 'S');

            const kls = params.get('Kls') || params.get('kls');
            if (kls && studentKelasSelect) {
                studentKelasSelect.value = kls;
                filterStudents(kls);
            }

            initTeacherFeatures();

            // Tambah Siswa Baru form handler
            const addStudentForm = document.getElementById('addStudentForm');
            if (addStudentForm) {
                addStudentForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Menambah...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });
                    fetch("{{ route('import.students') }}", {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            name: this.querySelector('[name="name"]').value,
                            grade: this.querySelector('[name="grade"]').value
                        })
                    }).then(r => r.json()).then(d => {
                        Swal.close();
                        if (d.status === 'success') {
                            Toast.fire({
                                icon: 'success',
                                title: 'Berhasil'
                            });
                            setTimeout(() => location.reload(), 700);
                        } else Swal.fire("Error", d.error, "error");
                    });
                });
            }
        });
    </script>
@endsection
