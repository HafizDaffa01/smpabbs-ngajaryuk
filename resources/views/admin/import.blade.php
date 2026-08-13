@extends('layouts.app')

@section('title', 'Import Data')

@section('content')

@if (session('success'))
    <div class="container pb-5">
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
@endif

@if (session('error'))
    <div class="container pb-5">
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
@endif

<div class="container pb-5">
    <!-- Header -->
    <div class="import-page-header animate__animated animate__fadeIn">
        <div class="d-flex align-items-center">
            <div class="card-icon">
                <i class="fas fa-file-import"></i>
            </div>
            <div class="header-text">
                <h2>Import Data</h2>
                <p>Upload file Excel untuk mengimpor data jadwal, guru, dan siswa ke dalam sistem.</p>
            </div>
        </div>
    </div>

    <!-- Preview Section -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="import-card">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="card-icon bg-soft-warning">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div>
                        <span class="badge bg-warning text-dark small fw-bold px-2 py-1 rounded-pill">STEP 1</span>
                        <h4 class="mb-0 mt-1">Preview File Sebelum Import</h4>
                    </div>
                </div>
                <p class="text-muted">Pilih file v9.4.xlsx untuk melihat isi sheet-nya sebelum melakukan import. Ini membantu memverifikasi bahwa file sesuai format yang diharapkan.</p>

                <div class="custom-file-upload" id="previewDropZone">
                    <input type="file" id="previewFileInput" class="form-control form-control-sm d-none" accept=".xlsx, .xls">
                    <label for="previewFileInput" class="file-upload-label">
                        <i class="fas fa-cloud-upload-alt me-2"></i>
                        <span>Klik atau seret file v9.4.xlsx ke sini</span>
                    </label>
                </div>

                <button type="button" id="previewBtn" class="btn btn-outline-warning w-100 mt-3" disabled>
                    <i class="fas fa-search me-1"></i>Preview File
                </button>
                <div id="previewLoading" class="mt-3 text-center d-none">
                    <div class="spinner-border text-warning" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 small text-muted">Membaca file...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Results Modal -->
    <div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-table me-2"></i>Preview File Excel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="previewModalBody">
                    <!-- Preview content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-primary" id="continueToImportBtn">
                        <i class="fas fa-upload me-1"></i>Lanjutkan ke Import
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- v9.4.xlsx Import -->
        <div class="col-lg-6">
            <div class="import-card">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="card-icon bg-soft-primary">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div>
                        <span class="badge bg-soft-primary text-primary small fw-bold px-2 py-1 rounded-pill">STEP 2</span>
                        <h4 class="mb-0 mt-1">Import v9.4.xlsx (Guru & Jadwal)</h4>
                    </div>
                </div>
                <p class="text-muted">File export aSc Timetables — satu file untuk guru & jadwal. Berisi sheet: Classes, Teachers, Lessons, mapel inti, Leadership, dan lainnya.</p>

                <div class="flex-fill">
                <form id="importScheduleForm" method="POST" action="{{ route('schedule.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="custom-file-upload mb-3">
                        <input type="file" name="file_v94" class="form-control form-control-sm d-none" accept=".xlsx, .xls" required id="scheduleFileInput">
                        <label for="scheduleFileInput" class="file-upload-label">
                            <i class="fas fa-cloud-upload-alt me-2"></i>
                            <span>Pilih File v9.4.xlsx</span>
                        </label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="confirm" value="1" id="importScheduleConfirm" required>
                        <label class="form-check-label" for="importScheduleConfirm">
                            Saya mengerti data lama akan dihapus dan digantikan
                        </label>
                    </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-upload me-1"></i>Import v9.4 (Guru & Jadwal)
                        </button>
                    </form>
                </div>

                <div class="info-text mt-3">
                    <i class="fas fa-info-circle me-1"></i>
                    Sheet yang diharapkan: <strong>Classes, Teachers, Lessons,</strong> 10 mapel inti, 3 Leadership, 4 tanpa guru
                </div>
            </div>
        </div>

        <!-- Student Excel Import -->
        <div class="col-lg-6">
            <div class="import-card">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="card-icon bg-soft-success">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div>
                        <span class="badge bg-soft-success text-success small fw-bold px-2 py-1 rounded-pill">STEP 2</span>
                        <h4 class="mb-0 mt-1">Import Data Siswa</h4>
                    </div>
                </div>
                <p class="text-muted">File Excel berisi sheet LEVEL 7, LEVEL 8, dan LEVEL 9. Setiap sheet berisi daftar siswa per kelas dengan kolom Nama dan Kelas. Progul (ICT-L, TCP, VCP) akan otomatis terisi jika tersedia di file.</p>

                <div class="flex-fill">
                <form id="importStudentsForm" method="POST" action="{{ route('import.students') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="custom-file-upload mb-3">
                        <input type="file" name="excel" class="form-control form-control-sm d-none" accept=".xlsx, .xls, .csv" required id="studentFileInput">
                        <label for="studentFileInput" class="file-upload-label">
                            <i class="fas fa-cloud-upload-alt me-2"></i>
                            <span>Pilih File Excel Siswa</span>
                        </label>
                    </div>
                    <button type="submit" class="btn btn-success w-100" id="importStudentsBtn">
                        <i class="fas fa-upload me-1"></i>Import Siswa
                    </button>
                </form>
                </div>

                <div class="info-text mt-3">
                    <i class="fas fa-check-circle me-1"></i>
                    Format yang didukung: Excel dengan sheet LEVEL 7, LEVEL 8, LEVEL 9.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Preview File Handler
    const previewFileInput = document.getElementById('previewFileInput');
    const previewBtn = document.getElementById('previewBtn');
    const previewLoading = document.getElementById('previewLoading');
    const previewModal = document.getElementById('previewModal');
    const previewModalBody = document.getElementById('previewModalBody');

    previewFileInput.addEventListener('change', function() {
        previewBtn.disabled = !this.files.length;
        updateFileLabel(this, 'previewDropZone');
    });

    // Drag and drop for preview
    const previewDropZone = document.getElementById('previewDropZone');
    if (previewDropZone) {
        previewDropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('drag-over');
        });

        previewDropZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('drag-over');
        });

        previewDropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('drag-over');
            const files = e.dataTransfer.files;
            if (files.length) {
                previewFileInput.files = files;
                previewBtn.disabled = false;
                updateFileLabel(previewFileInput, 'previewDropZone');
            }
        });
    }

    previewBtn.addEventListener('click', function() {
        const file = previewFileInput.files[0];
        if (!file) return;

        previewBtn.disabled = true;
        previewLoading.style.display = 'block';
        previewModalBody.innerHTML = '<p class="text-center">Membaca file...</p>';

        const formData = new FormData();
        formData.append('file_v94', file);
        formData.append('_token', '{{ csrf_token() }}');

        fetch('{{ route("schedule.preview") }}', {
            method: 'POST',
            body: formData,
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error('Invalid JSON response: ' + text.substring(0, 200));
                }
            });
        })
        .then(data => {
            previewLoading.style.display = 'none';
            previewBtn.disabled = false;

            if (data.status === 'error') {
                previewModalBody.innerHTML = '<div class="alert alert-danger">Error: ' + data.message + '</div>';
                new bootstrap.Modal(previewModal).show();
                return;
            }

            let html = '';
            for (const [sheetName, rows] of Object.entries(data.sheets)) {
                html += '<h6 class="mt-3 mb-2"><strong>' + sheetName + '</strong> (' + rows.length + ' baris)</h6>';
                html += '<div class="table-responsive mb-3">';
                html += '<table class="table table-sm table-bordered table-striped">';
                rows.forEach((row, idx) => {
                    html += '<tr>';
                    row.forEach(cell => {
                        html += '<td class="text-nowrap cell-truncate">' + (cell || '') + '</td>';
                    });
                    html += '</tr>';
                });
                html += '</table>';
                html += '</div>';
            }

            previewModalBody.innerHTML = html;
            new bootstrap.Modal(previewModal).show();
        })
        .catch(error => {
            previewLoading.style.display = 'none';
            previewBtn.disabled = false;
            previewModalBody.innerHTML = '<div class="alert alert-danger">Error: ' + error.message + '</div>';
            new bootstrap.Modal(previewModal).show();
        });
    });

    // Continue to Import button (copy file from preview to import form)
    var continueToImportBtn = document.getElementById('continueToImportBtn');
    if (continueToImportBtn) {
        continueToImportBtn.addEventListener('click', function() {
            var previewFile = document.getElementById('previewFileInput').files[0];
            var importFileInput = document.getElementById('importScheduleForm').querySelector('input[name="file_v94"]');
            
            if (previewFile && importFileInput) {
                var dataTransfer = new DataTransfer();
                dataTransfer.items.add(previewFile);
                importFileInput.files = dataTransfer.files;
            }
            
            var importForm = document.getElementById('importScheduleForm');
            if (importForm) {
                importForm.submit();
            }
        });
    }

    // Import Jadwal Form Handler
    var importScheduleForm = document.getElementById('importScheduleForm');
    if (importScheduleForm) {
        importScheduleForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var form = this;

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Sedang Mengimport Jadwal...',
                    text: 'Mohon tunggu sebentar, sistem sedang memproses file Excel Anda.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                        form.submit();
                    }
                });
            } else {
                form.submit();
            }
        });
    }

    // Import Siswa Form Handler
    var importStudentsForm = document.getElementById('importStudentsForm');
    if (importStudentsForm) {
        importStudentsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var form = this;

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Sedang Mengimport Siswa...',
                    text: 'Mohon tunggu sebentar, sistem sedang memproses file Excel Anda.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                        form.submit();
                    }
                });
            } else {
                form.submit();
            }
        });
    }

    // File upload label update
    function updateFileLabel(input, dropZoneId) {
        var dropZone = document.getElementById(dropZoneId);
        var label = dropZone ? dropZone.querySelector('.file-upload-label') : null;
        if (!label) return;

        if (input.files && input.files.length > 0) {
            var fileName = input.files[0].name;
            label.innerHTML = '<i class="fas fa-file-check me-2"></i><span>' + fileName + '</span>';
            label.classList.add('has-file');
        } else {
            label.innerHTML = '<i class="fas fa-cloud-upload-alt me-2"></i><span>Klik atau seret file ke sini</span>';
            label.classList.remove('has-file');
        }
    }

    // Schedule file input
    var scheduleFileInput = document.getElementById('scheduleFileInput');
    if (scheduleFileInput) {
        scheduleFileInput.addEventListener('change', function() {
            updateFileLabel(this, 'scheduleFileInput');
        });
    }

    // Student file input
    var studentFileInput = document.getElementById('studentFileInput');
    if (studentFileInput) {
        studentFileInput.addEventListener('change', function() {
            updateFileLabel(this, 'studentFileInput');
        });
    }
</script>

@endsection
