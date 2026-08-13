@extends('layouts.app')

@section('title', 'Explorer')

@section('content')
    <div class="container-fluid explorer-container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-color">
            <h1 class="h3 m-0 fw-800">
                <i class="fas fa-folder-open text-primary me-2"></i>File Explorer
            </h1>
            <div class="bg-soft-primary px-3 py-2 rounded-lg">
                <span class="text-primary small fw-800 tracking-wider">/uploads{{ $path ? '/' . $path : '' }}</span>
            </div>
        </div>

        {{-- Navigation & Actions --}}
        <div class="row g-4 mb-4 align-items-center">
            <div class="col-md-6">
                @if ($path)
                    @php
                        $parent = dirname($path);
                        if ($parent == '.') {
                            $parent = '';
                        }
                    @endphp
                    <a href="{{ url('/explorer?path=' . $parent) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Kembali
                    </a>
                @endif
                <span class="ms-3 text-muted small">
                    <strong>Lokasi:</strong> <span class="text-main">/uploads{{ $path ? '/' . $path : '' }}</span>
                </span>
            </div>
            <div class="col-md-6">
                <div class="d-flex justify-content-md-end align-items-center gap-2">
                    <div class="input-group input-group-sm input-group-search">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" id="searchInput" class="form-control" placeholder="Cari...">
                    </div>
                    <select id="sortSelect" class="form-select form-select-sm sort-select">
                        <option value="az">A-Z</option>
                        <option value="za">Z-A</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Folders --}}
            <div class="col-md-6">
                <h4 class="h5 fw-800 mb-3"><i class="fas fa-folder text-warning me-2"></i>Folders</h4>
                <div class="table-responsive table-scroll">
                    <table class="table table-hover align-middle folder-table">
                        <thead>
                            <tr>
                                <th>Nama Folder</th>
                                <th class="action-col">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dirs as $dir)
                                @php $folderName = basename($dir); @endphp
                                <tr>
                                    <td class="fw-600">
                                        <i class="fas fa-folder text-warning me-2 opacity-75"></i>
                                        <a
                                            href="{{ url('/explorer?path=' . trim(($path ? $path . '/' : '') . $folderName)) }}">
                                            {{ $folderName }}
                                        </a>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <form action="{{ route('explorer.rename') }}" method="POST"
                                                class="d-flex gap-1">
                                                @csrf
                                                <input type="hidden" name="path" value="{{ $path }}">
                                                <input type="hidden" name="old_name" value="{{ $folderName }}">
                                                <input type="hidden" name="type" value="folder">
                                                <input type="text" name="new_name" placeholder="Nama Baru"
                                                    class="form-control form-control-sm rename-input" required>
                                                <button class="btn btn-sm btn-outline-warning" title="Ubah Nama">
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                            </form>
                                            <button class="btn btn-sm btn-outline-danger"
                                                onclick="confirmDeleteFolder('{{ $folderName }}')" title="Hapus">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center py-4 text-muted small italic">Kosong</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Files --}}
            <div class="col-md-6">
                <h4 class="h5 fw-800 mb-3"><i class="fas fa-file-alt text-info me-2"></i>Files</h4>
                <div class="table-responsive table-scroll">
                    <table class="table table-hover align-middle file-table">
                        <thead>
                            <tr>
                                <th>Nama File</th>
                                <th class="action-col">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($files as $file)
                                @php $fileName = basename($file); @endphp
                                <tr>
                                    <td class="fw-600">
                                        <i class="fas fa-file text-info me-2 opacity-75"></i>
                                        {{ $fileName }}
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <form action="{{ route('explorer.rename') }}" method="POST"
                                                class="d-flex gap-1">
                                                @csrf
                                                <input type="hidden" name="path" value="{{ $path }}">
                                                <input type="hidden" name="old_name" value="{{ $fileName }}">
                                                <input type="hidden" name="type" value="file">
                                                <input type="text" name="new_name" placeholder="Nama Baru"
                                                    class="form-control form-control-sm rename-input" required>
                                                <button class="btn btn-sm btn-outline-warning" title="Ubah Nama">
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                            </form>
                                            <a href="{{ asset('uploads/' . ($path ? $path . '/' : '') . $fileName) }}"
                                                target="_blank" class="btn btn-sm btn-outline-success" title="Lihat">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <form action="{{ route('explorer.deleteFile') }}" method="POST"
                                                class="d-inline">
                                                @csrf @method('DELETE')
                                                <input type="hidden" name="path" value="{{ $path }}">
                                                <input type="hidden" name="name" value="{{ $fileName }}">
                                                <button class="btn btn-sm btn-outline-danger" title="Hapus">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center py-4 text-muted small italic">Kosong</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <hr>

        <div class="row g-4 mt-2">
            {{-- Create folder --}}
            <div class="col-md-6">
                <div class="card bg-soft-primary border-0 shadow-none">
                    <div class="card-body">
                        <h5 class="fw-800 mb-3"><i class="fas fa-folder-plus text-primary me-2"></i>Buat Folder</h5>
                        <form action="{{ route('explorer.folder') }}" method="post">
                            @csrf
                            <input type="hidden" name="path" value="{{ $path }}">
                            <div class="input-group">
                                <input type="text" name="name" class="form-control" placeholder="Nama folder..."
                                    required>
                                <button class="btn btn-primary px-4">Buat</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Upload file --}}
            <div class="col-md-6">
                <div class="card bg-soft-success border-0 shadow-none">
                    <div class="card-body">
                        <h5 class="fw-800 mb-3"><i class="fas fa-file-upload text-success me-2"></i>Upload File</h5>
                        <form action="{{ route('explorer.upload') }}" method="post" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="path" value="{{ $path }}">
                            <div class="input-group">
                                <input type="file" name="file" class="form-control" required>
                                <button class="btn btn-success px-4">Upload</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- === SweetAlert2 Logic === --}}

    <script>
        function confirmDeleteFolder(folderName) {
            const captchaCode = Math.floor(10000 + Math.random() * 90000).toString();

            Swal.fire({
                title: 'Konfirmasi Hapus Folder',
                html: `
            Masukkan kode berikut untuk mengonfirmasi:<br><br>
            <b class="captcha-code">${captchaCode}</b>
        `,
                icon: 'warning',
                input: 'number',
                inputPlaceholder: 'Masukkan kode di atas',
                showCancelButton: true,
                confirmButtonText: 'Hapus',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#d33',
                preConfirm: (value) => {
                    if (value !== captchaCode) {
                        Swal.showValidationMessage('Kode tidak cocok!');
                        return false;
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = "{{ route('explorer.deleteFolder') }}";

                    form.innerHTML = `
                @csrf
                @method('DELETE')
                <input type="hidden" name="path" value="{{ $path }}">
                <input type="hidden" name="name" value="${folderName}">
            `;

                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        // === SEARCH & SORT ===
        document.getElementById('searchInput').addEventListener('input', filterTables);
        document.getElementById('sortSelect').addEventListener('change', sortTables);

        function filterTables() {
            const search = document.getElementById('searchInput').value.toLowerCase();

            document.querySelectorAll('table tbody tr').forEach(row => {
                const nameCell = row.querySelector('td');
                if (!nameCell) return;
                const text = nameCell.innerText.toLowerCase();
                row.style.display = text.includes(search) ? "" : "none";
            });
        }

        function sortTables() {
            const order = document.getElementById('sortSelect').value;
            const tables = document.querySelectorAll('table tbody');

            tables.forEach(tbody => {
                const rows = Array.from(tbody.querySelectorAll('tr'))
                    .filter(r => r.querySelector('td') && !r.querySelector('td').classList.contains('text-muted'));

                rows.sort((a, b) => {
                    const nameA = a.querySelector('td').innerText.toLowerCase();
                    const nameB = b.querySelector('td').innerText.toLowerCase();
                    return order === 'az' ? nameA.localeCompare(nameB) : nameB.localeCompare(nameA);
                });

                rows.forEach(r => tbody.appendChild(r));
            });
        }
    </script>
@endsection
