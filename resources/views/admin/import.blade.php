@extends('layouts.app')

@section('title', 'Import Data')

@section('content')

<style>
    .import-page-header {
        background: var(--bg-card);
        color: var(--text-main);
        padding: 2rem;
        border-radius: var(--card-radius);
        margin-bottom: 2rem;
        box-shadow: var(--card-shadow);
        border: 1px solid var(--border-color);
        position: relative;
        overflow: hidden;
    }

    .import-page-header::after {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 300px;
        height: 100%;
        background: linear-gradient(90deg, transparent 0%, rgba(59, 130, 246, 0.05) 100%);
        pointer-events: none;
    }

    .import-page-header h2 {
        font-size: 1.75rem;
        font-weight: 800;
        color: var(--text-main);
        margin-bottom: 0.25rem;
    }

    .import-page-header p {
        color: var(--text-muted);
        margin-bottom: 0;
    }

    .import-card {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--card-radius);
        box-shadow: var(--card-shadow);
        padding: 2rem;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .import-card .card-icon {
        width: 56px;
        height: 56px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 1rem;
    }

    .import-card h4 {
        font-weight: 800;
        color: var(--text-main);
        margin-bottom: 0.5rem;
    }

    .import-card p {
        color: var(--text-muted);
        font-size: 0.9rem;
        margin-bottom: 1.5rem;
    }

    .import-card .form-control {
        background-color: #0f172a;
        color: var(--text-main);
        border: 1.5px solid var(--border-color);
        border-radius: 8px;
        padding: 0.6rem 1rem;
    }

    .import-card .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        background-color: #0f172a;
        color: var(--text-main);
    }

    .import-card .form-check-label {
        color: var(--text-muted);
        font-size: 0.85rem;
    }

    .import-card .form-check-input {
        background-color: #0f172a;
        border-color: var(--border-color);
    }

    .import-card .form-check-input:checked {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }

    .info-text {
        color: var(--text-muted);
        font-size: 0.8rem;
        margin-top: 1rem;
    }

    .info-text i {
        color: var(--primary-color);
    }
    </style>

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
            <div class="card-icon me-4" style="background: rgba(59, 130, 246, 0.15); color: #60a5fa;">
                <i class="fas fa-file-import"></i>
            </div>
            <div>
                <h2>Import Data</h2>
                <p>Upload file Excel untuk mengimpor data jadwal, guru, dan siswa ke dalam sistem.</p>
            </div>
        </div>
    </div>

    <!-- Preview Section -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="import-card">
                <div class="card-icon" style="background: rgba(255, 193, 7, 0.15); color: #ffc107;">
                    <i class="fas fa-eye"></i>
                </div>
                <h4>Preview File Sebelum Import</h4>
                <p>Pilih file v9.4.xlsx untuk melihat isi sheet-nya sebelum melakukan import. Ini membantu memverifikasi bahwa file sesuai format yang diharapkan.</p>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-uppercase" style="color: var(--text-muted);">Pilih File untuk Preview</label>
                    <input type="file" id="previewFileInput" class="form-control form-control-sm" accept=".xlsx, .xls">
                </div>
                <button type="button" id="previewBtn" class="btn btn-warning w-100" disabled>
                    <i class="fas fa-search me-1"></i>Preview File
                </button>
                <div id="previewLoading" class="mt-3 text-center" style="display:none;">
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
                <div class="card-icon" style="background: rgba(59, 130, 246, 0.15); color: #60a5fa;">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h4>Import v9.4.xlsx (Guru & Jadwal)</h4>
                <p>File export aSc Timetables — satu file untuk guru & jadwal. Berisi sheet: Classes, Teachers, Lessons, mapel inti, Leadership, dan lainnya.</p>

                <form id="importScheduleForm" method="POST" action="{{ route('schedule.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase" style="color: var(--text-muted);">Pilih File v9.4.xlsx</label>
                        <input type="file" name="file_v94" class="form-control form-control-sm" accept=".xlsx, .xls" required>
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

                <div class="info-text mt-3">
                    <i class="fas fa-info-circle me-1"></i>
                    Sheet yang diharapkan: <strong>Classes, Teachers, Lessons,</strong> 10 mapel inti, 3 Leadership, 4 tanpa guru
                </div>
            </div>
        </div>

        <!-- Student Excel Import (Future) -->
        <div class="col-lg-6">
            <div class="import-card">
                <div class="card-icon" style="background: rgba(16, 185, 129, 0.15); color: #34d399;">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <h4>Import Data Siswa</h4>
                <p>File Excel multi-sheet (per kelas) untuk mengimpor daftar siswa. Format akan tersedia pada pembaruan berikutnya.</p>

                <form id="importStudentsForm" method="POST" action="{{ route('import.students') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase" style="color: var(--text-muted);">Pilih File Excel</label>
                        <input type="file" name="excel" class="form-control form-control-sm" accept=".xlsx, .xls, .csv" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100" id="importStudentsBtn">
                        <i class="fas fa-upload me-1"></i>Import Siswa
                    </button>
                </form>

                <div class="info-text mt-3">
                    <i class="fas fa-clock me-1"></i>
                    Fitur import siswa akan segera tersedia.
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
    });

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
                        html += '<td class="text-nowrap" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">' + (cell || '') + '</td>';
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
</script>

@endsection
