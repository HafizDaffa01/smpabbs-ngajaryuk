@extends('layouts.app')

@section('title', 'Backup Dashboard')

@section('content')
    <div class="container-fluid px-4 py-3">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 fw-bold text-primary">
                <i class="fas fa-database me-2"></i>Backup Panel
            </h1>
        </div>

        {{-- =================== FILTER =================== --}}
        <div class="card shadow-sm mb-4">
            <div class="card-body">

                <form method="get" id="filterForm" action="{{ route('backup.index') }}" class="row g-2 align-items-end">

                    {{-- ================= FILTER LEFT ================= --}}
                    <div class="col d-flex gap-2 flex-wrap">

                        {{-- Bulan --}}
                        <div style="min-width:140px">
                            <label class="form-label small text-muted mb-1">
                                <i class="far fa-calendar me-1"></i>Bulan
                            </label>
                            <select name="month" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="" disabled selected>Pilih Bulan</option>
                                @foreach ($months as $key => $label)
                                    <option value="{{ $key }}" {{ $filterMonth == $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Tahun --}}
                        <div style="min-width:140px">
                            <label class="form-label small text-muted mb-1">
                                <i class="far fa-calendar-alt me-1"></i>Tahun
                            </label>
                            <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="" disabled selected>Pilih Tahun</option>
                                @foreach ($years as $y)
                                    <option value="{{ $y }}" {{ $filterYear == $y ? 'selected' : '' }}>
                                        {{ $y }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Tipe Data --}}
                        <div style="min-width:140px">
                            <label class="form-label small text-muted mb-1">
                                <i class="fas fa-filter me-1"></i>Tipe Data
                            </label>
                            <select name="data_type" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="" disabled selected>Pilih Tipe</option>
                                <option value="waktu" {{ $dataType === 'waktu' ? 'selected' : '' }}>Waktu</option>
                                <option value="lokasi" {{ $dataType === 'lokasi' ? 'selected' : '' }}>Lokasi</option>
                                <option value="gambar" {{ $dataType === 'gambar' ? 'selected' : '' }}>Gambar</option>
                            </select>
                        </div>

                        {{-- Search --}}
                        <div style="min-width:180px">
                            <label class="form-label small text-muted mb-1">
                                <i class="fas fa-search me-1"></i>Pencarian
                            </label>
                            <input type="text" name="search" class="form-control form-control-sm"
                                placeholder="Cari nama..." value="{{ $search }}">
                        </div>

                    </div>
                    {{-- ================= END FILTER LEFT ================= --}}

                    {{-- ================= ACTION RIGHT ================= --}}
                    <div class="col-auto d-flex gap-2 align-items-end">

                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-search me-1"></i>Cari
                        </button>

                        <button type="button" id="toggleSort" class="btn btn-outline-secondary btn-sm"
                            title="Urutkan Nama">
                            <i class="fas fa-sort-alpha-down"></i>
                        </button>

                    </div>

                    {{-- ================= EXTRA (WAKTU) ================= --}}
                    @if ($dataType === 'waktu')
                        <div class="col-auto d-flex gap-3 align-items-end">

                            <button type="button" onclick="saveAllData()" class="btn btn-success btn-sm">
                                <i class="fas fa-save me-1"></i>Scan & Simpan Perubahan
                            </button>

                            <div class="d-flex align-items-center gap-2">
                                <span class="text-muted">Auto Save:</span>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" id="autoSave" style="cursor:pointer">
                                </div>
                            </div>

                        </div>
                    @endif
                    {{-- ================= END EXTRA ================= --}}

                </form>

            </div>
        </div>


        {{-- =================== TABEL WAKTU =================== --}}
        <div id="table-container">
            @if ($dataType === 'waktu' && isset($days) && isset($gridData))
                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <div style="overflow-x:auto; max-width:100%;" class="table-wrapper">
                            <table class="table table-hover table-bordered align-middle text-center mb-0">
                                <thead>
                                    <tr>
                                        <th rowspan="2" class="sticky-col sticky-header" style="left:0;">No</th>
                                        <th rowspan="2" class="sticky-col sticky-header" style="left:50px;">
                                            <i class="fas fa-user me-1"></i>Nama
                                        </th>
                                        <th colspan="{{ count($days) }}" class="sticky-header">
                                            {{ $months[$filterMonth] ?? '' }} {{ $filterYear }}
                                        </th>
                                        <th rowspan="2" class="sticky-col-right sticky-header" style="right:0;">
                                            <i class="fas fa-calculator me-1"></i>TOT
                                        </th>
                                    </tr>
                                    <tr>
                                        @foreach ($days as $d)
                                            <th class="sticky-header small">
                                                {{ $d->format('d') }}
                                                <div style="font-size:9px; font-weight:normal;">
                                                    {{ substr($d->translatedFormat('D'), 0, 3) }}
                                                </div>
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $no = 1; @endphp
                                    @foreach ($gridData as $nama => $row)
                                        @php $tot = 0; @endphp
                                        <tr data-nama="{{ $nama }}" data-unit="{{ $row['unit'] ?? '' }}">
                                            <td class="sticky-col" style="left:0;">{{ $no++ }}</td>
                                            <td class="sticky-col text-start" style="left:50px;">
                                                {{ $nama }}
                                            </td>
                                            @foreach ($days as $d)
                                                @php
                                                    $tgl = $d->format('Y-m-d');
                                                    $val = $row['data'][$tgl] ?? '-';
                                                    if ($val !== '-' && $val <= '06:50') {
                                                        $tot++;
                                                    }
                                                @endphp
                                                <td data-date="{{ $tgl }}" onclick="enableEdit(this)"
                                                    class="cell-editable date-col" style="cursor:pointer;">
                                                    {{ $val }}
                                                </td>
                                            @endforeach
                                            <td class="sticky-col-right fw-bold" style="right:0;">
                                                {{ $tot }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- =================== TABEL LOKASI =================== --}}
        @if ($dataType === 'lokasi')
            <div id="table-container">
                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <div style="overflow-x:auto; max-width:100%;" class="table-wrapper">
                            <table class="table table-hover table-bordered align-middle text-center mb-0">
                                <thead>
                                    <tr>
                                        <th rowspan="2" class="sticky-col sticky-header" style="left:0;">No</th>
                                        <th rowspan="2" class="sticky-col sticky-header" style="left:50px;">
                                            <i class="fas fa-user me-1"></i>Nama
                                        </th>
                                        <th colspan="{{ count($days) }}" class="sticky-header">
                                            {{ $months[$filterMonth] ?? '' }} {{ $filterYear }}
                                        </th>
                                    </tr>
                                    <tr>
                                        @foreach ($days as $d)
                                            <th class="sticky-header small date-col">
                                                {{ $d->format('d') }}
                                                <div style="font-size:9px; font-weight:normal;">
                                                    {{ substr($d->translatedFormat('D'), 0, 3) }}
                                                </div>
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $no = 1; @endphp
                                    @foreach ($gridData as $nama => $row)
                                        <tr data-nama="{{ $nama }}" data-unit="{{ $row['unit'] ?? '' }}">
                                            <td class="sticky-col" style="left:0;">{{ $no++ }}</td>
                                            <td class="sticky-col text-start" style="left:50px;">
                                                {{ $nama }}
                                            </td>
                                            @foreach ($days as $d)
                                                @php
                                                    $tgl = $d->format('Y-m-d');
                                                    $absen = $row['data'][$tgl] ?? null;
                                                @endphp
                                                <td
                                                    @if ($absen && !empty($absen->lokasi)) class="cell-clickable date-col"
                                                onclick="showMap('{{ $absen->lokasi }}', '{{ $nama }}', '{{ \Carbon\Carbon::parse($absen->waktu)->format('d F Y, H:i:s') }}', '{{ $absen->alamat }}');" @else class="date-col" @endif>
                                                    @if ($absen)
                                                        <span class="small">
                                                            <i class="fas fa-map-marker-alt text-danger me-1"></i>
                                                            {{ trim(explode(',', $absen->alamat ?? '-')[0]) }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- =================== TABEL GAMBAR =================== --}}
        @if ($dataType === 'gambar')
            @php
                $totalImages = 0;
                foreach ($gridData as $row) {
                    foreach ($row['data'] as $item) {
                        if (isset($item) && !empty($item->foto)) {
                            $totalImages++;
                        }
                    }
                }
                $warningMode = $totalImages >= 30;
            @endphp

            @if ($warningMode)
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        if (typeof showToast === 'function') {
                            showToast(
                                "Tumbnail tidak dapat ditampilkan secara otomatis karena jumlah gambar melebihi 30. Gunakan klik untuk melihat.",
                                "warning"
                            );
                        }
                    });
                </script>
            @endif

            <div id="table-container">
                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <div style="overflow-x:auto; max-width:100%;" class="table-wrapper">
                            <table class="table table-hover table-bordered align-middle text-center mb-0">
                                <thead>
                                    <tr>
                                        <th rowspan="2" class="sticky-col sticky-header" style="left:0;">No</th>
                                        <th rowspan="2" class="sticky-col sticky-header" style="left:50px;">
                                            <i class="fas fa-user me-1"></i>Nama
                                        </th>
                                        <th colspan="{{ count($days) }}" class="sticky-header">
                                            {{ $months[$filterMonth] ?? '' }} {{ $filterYear }}
                                        </th>
                                    </tr>
                                    <tr>
                                        @foreach ($days as $d)
                                            <th class="sticky-header small date-col">
                                                {{ $d->format('d') }}
                                                <div style="font-size:9px; font-weight:normal;">
                                                    {{ substr($d->translatedFormat('D'), 0, 3) }}
                                                </div>
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $no = 1; @endphp
                                    @foreach ($gridData as $nama => $row)
                                        <tr data-nama="{{ $nama }}" data-unit="{{ $row['unit'] ?? '' }}">
                                            <td class="sticky-col" style="left:0;">{{ $no++ }}</td>
                                            <td class="sticky-col text-start" style="left:50px;">
                                                {{ $nama }}
                                            </td>

                                            @foreach ($days as $d)
                                                @php
                                                    $tgl = $d->format('Y-m-d');
                                                    $absen = $row['data'][$tgl] ?? null;
                                                @endphp
                                                <td class="date-col">

                                                    @if (isset($absen) && !empty($absen->foto))
                                                        @if ($warningMode)
                                                            {{-- MODE WARNING --}}
                                                            <div class="text-warning" style="cursor:pointer"
                                                                onclick="showImage('{{ url('/' . $absen->foto) }}','{{ $nama }}','{{ $tgl }}')">
                                                                <i class="fas fa-exclamation-triangle"
                                                                    style="font-size:24px;"></i>
                                                            </div>
                                                        @else
                                                            {{-- MODE NORMAL --}}
                                                            <img src="{{ url('/' . $absen->foto) }}" width="60"
                                                                height="60" class="img-thumbnail"
                                                                style="object-fit:cover;cursor:pointer"
                                                                onclick="showImage('{{ url('/' . $absen->foto) }}','{{ $nama }}','{{ $tgl }}')">
                                                        @endif
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif

                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- =================== ACTION BUTTONS =================== --}}
        <div class="d-flex gap-2 mt-4">
            <button type="button" class="btn btn-outline-danger" onclick="openDeleteByPeriodModal()">
                <i class="fas fa-calendar-times me-1"></i>Hapus per Bulan
            </button>

            <button type="button" class="btn btn-outline-danger" onclick="openDeleteModal()">
                <i class="fas fa-trash-alt me-1"></i>Hapus Semua Absensi
            </button>

            <a href="{{ route('backup.export' . ucfirst($dataType), [
                'month' => $filterMonth,
                'year' => $filterYear,
                'search' => $search,
            ]) }}"
                class="btn btn-primary">
                <i class="fas fa-download me-1"></i>Backup Data
            </a>
        </div>
    </div>

    {{-- =================== NOTIFIKASI =================== --}}
    <div id="notification-container"></div>

    {{-- =================== STYLE =================== --}}
    <style>
        /* --- GENERAL --- */
        body {
            background-color: var(--bg-body);
            color: var(--text-main);
        }

        /* --- TABEL --- */
        /* No column */
        td:nth-child(1),
        th:nth-child(1) {
            width: 50px !important;
            min-width: 50px !important;
        }

        /* Nama column */
        td:nth-child(2),
        th:nth-child(2) {
            width: 220px !important;
            min-width: 220px !important;
            max-width: 220px !important;
        }

        /* TOT column (Waktu table) */
        .sticky-col-right {
            width: 80px !important;
            min-width: 80px !important;
        }

        .cell-editable:hover {
            background-color: rgba(255, 243, 205, 0.1) !important;
        }

        .cell-clickable {
            cursor: pointer;
            transition: background 0.2s;
        }

        .cell-clickable:hover {
            background-color: rgba(59, 130, 246, 0.15) !important;
        }

        .cell-editable input {
            background-color: #0f172a;
            border: 1px solid var(--primary-color);
            color: #ffffff;
        }

        .saving {
            background: rgba(59, 130, 246, 0.15) !important;
            outline: 2px solid var(--primary-color);
        }

        .saved-success {
            background: rgba(16, 185, 129, 0.15) !important;
            outline: 2px solid var(--success-color);
        }

        .saved-error {
            background: rgba(239, 68, 68, 0.15) !important;
            outline: 2px solid var(--danger-color);
        }

        .editing {
            background: rgba(245, 158, 11, 0.15) !important;
            outline: 2px solid var(--warning-color);
        }

        /* --- IMAGE LAZY LOADING --- */
        .lazy-image {
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }

        .lazy-image.loaded {
            opacity: 1;
        }

        /* --- NOTIFIKASI --- */
        #notification-container {
            position: fixed;
            top: 80px;
            right: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            z-index: 9999;
            max-width: 350px;
        }

        .notification {
            padding: 14px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            font-size: 0.9rem;
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .notification.show {
            opacity: 1;
            transform: translateX(0);
        }

        .notification i {
            font-size: 1.2rem;
        }

        .notif-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .notif-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        .notif-warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }

        .notif-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }

        /* --- FILTERS --- */
        .form-label.text-muted {
            color: #cbd5e1 !important;
            font-weight: 600;
        }

        .text-muted {
            color: #aeb9cc !important;
        }

        /* --- STICKY TABEL --- */
        .sticky-col {
            position: sticky;
            background: #1e293b !important;
            z-index: 10;
            /* Increased to ensure it stays on top of other body cells */
            color: #ffffff;
            border-right: 1px solid #334155 !important;
        }

        .sticky-col-right {
            position: sticky;
            background: #1e293b !important;
            z-index: 10;
            color: #ffffff;
            border-left: 1px solid #334155 !important;
            font-weight: bold;
        }

        .sticky-header {
            position: sticky;
            top: 0;
            background: #2d3748 !important;
            z-index: 20;
            /* Stays on top of sticky columns */
            color: #ffffff !important;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }

        /* Combined Sticky (Corners) */
        .sticky-header.sticky-col,
        .sticky-header.sticky-col-right {
            z-index: 30 !important;
            /* Highest priority */
            background: #2d3748 !important;
        }

        .table thead th {
            background: #2d3748 !important;
            color: #ffffff !important;
            border-bottom: 2px solid #4a5568 !important;
        }

        .table tbody td {
            color: #f1f5f9;
            background-color: #1e293b;
            border-color: #334155 !important;
        }

        .table-hover tbody tr:hover td {
            background-color: #2d3748 !important;
        }

        .table {
            table-layout: fixed !important;
            width: max-content !important;
            min-width: 100%;
            border-collapse: separate !important;
            /* Required for sticky to work correctly with borders */
            border-spacing: 0;
        }

        .date-col {
            width: 70px !important;
            min-width: 70px !important;
            max-width: 70px !important;
            padding-left: 2px !important;
            padding-right: 2px !important;
            text-align: center;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .table-wrapper::-webkit-scrollbar {
            height: 10px;
            width: 8px;
        }

        .table-wrapper::-webkit-scrollbar-track {
            background: #0f172a;
        }

        .table-wrapper::-webkit-scrollbar-thumb {
            background: #475569;
            border-radius: 4px;
        }

        .table-wrapper::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }

        .btn-outline-danger {
            border-width: 1.5px;
        }

        .btn-outline-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.3);
        }

        /* --- RESPONSIVE --- */
        @media (max-width: 768px) {
            .container-fluid {
                padding-left: 10px;
                padding-right: 10px;
            }

            td:nth-child(2),
            th:nth-child(2) {
                width: 180px !important;
                min-width: 180px !important;
            }
        }
    </style>

    {{-- =================== SCRIPT =================== --}}
    <script>
        // === MAP VIEWER ===
        function showMap(lokasi, nama, waktu, alamat) {
            const [lat, lon] = lokasi.split(',').map(x => parseFloat(x.trim()));
            Swal.fire({
                title: `<i class="fas fa-user-circle"></i> ${nama}`,
                html: `
            <div class="text-start">
                <p class="mb-2"><i class="far fa-clock text-primary"></i> <b>Waktu:</b> ${waktu}</p>
                <p class="mb-3"><i class="fas fa-map-marker-alt text-danger"></i> <b>Alamat:</b> ${alamat}</p>
                <div id="mapPrev" style="width:100%;height:350px;border-radius:8px;"></div>
            </div>
        `,
                didOpen: () => {
                    const map = L.map('mapPrev').setView([lat, lon], 16);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap'
                    }).addTo(map);
                    L.marker([lat, lon]).addTo(map)
                        .bindPopup(`<b><i class="fas fa-map-pin"></i> ${nama}</b>`)
                        .openPopup();
                },
                width: 700,
                showCloseButton: true,
                showConfirmButton: false
            });
        }

        // === IMAGE VIEWER ===
        function showImage(url, nama, date) {
            Swal.fire({
                title: `<i class="fas fa-user-circle"></i> ${nama}`,
                text: `Tanggal: ${date}`,
                imageUrl: url,
                imageAlt: nama,
                imageWidth: 500,
                imageHeight: 400,
                showCloseButton: true,
                showConfirmButton: false,
                customClass: {
                    image: 'img-fluid rounded'
                }
            });
        }

        // === LAZY LOADING IMAGES ===
        document.addEventListener('DOMContentLoaded', function() {
            const lazyImages = document.querySelectorAll('.lazy-image');

            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.onload = () => img.classList.add('loaded');
                        observer.unobserve(img);
                    }
                });
            }, {
                rootMargin: '50px'
            });

            lazyImages.forEach(img => imageObserver.observe(img));
        });

        // === NOTIFICATIONS ===
        function showToast(message, type = "success", delay = 3000) {
            const container = document.getElementById("notification-container");
            const notif = document.createElement("div");
            notif.className = `notification notif-${type}`;

            const icons = {
                success: 'fas fa-check-circle',
                danger: 'fas fa-exclamation-circle',
                warning: 'fas fa-exclamation-triangle',
                primary: 'fas fa-info-circle'
            };

            notif.innerHTML = `<i class="${icons[type] || icons.primary}"></i><span>${message}</span>`;
            container.appendChild(notif);

            setTimeout(() => notif.classList.add("show"), 100);
            setTimeout(() => {
                notif.classList.remove("show");
                setTimeout(() => notif.remove(), 300);
            }, delay);
        }

        // === SAVE DATA ===
        function showLoading(td) {
            td.classList.add("saving");
        }

        function hideLoading(td, status = null) {
            td.classList.remove("saving");
            if (status === "success") {
                td.classList.add("saved-success");
                setTimeout(() => td.classList.remove("saved-success"), 1500);
            } else if (status === "error") {
                td.classList.add("saved-error");
                setTimeout(() => td.classList.remove("saved-error"), 1500);
            }
        }

        async function saveData(td, delay_ = 3000) {
            let original = td.dataset.original ?? td.innerText.trim();
            let current = td.innerText.trim();

            // ⛔ SKIP JIKA TIDAK BERUBAH
            if (original === current) {
                clearUnsaved(td);
                return false;
            }

            let row = td.closest("tr");
            let nama = row.dataset.nama;
            let unit = row.dataset.unit;
            let date = td.getAttribute("data-date");
            let value = current || "-";
            if (value === "-") return false;

            try {
                showLoading(td);

                const res = await fetch("{{ route('backup.save') }}", {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": "{{ csrf_token() }}",
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        nama,
                        unit,
                        date,
                        value,
                        data_type: 'waktu'
                    })
                });

                const json = await res.json();

                if (res.ok) {
                    td.innerText = json.value ?? value;

                    // 🔥 UPDATE ORIGINAL SETELAH SAVE
                    td.dataset.original = td.innerText.trim();

                    clearUnsaved(td);
                    hideLoading(td, "success");

                    showToast(`Data ${nama} berhasil disimpan!`, "success", delay_);
                    return true;
                } else {
                    hideLoading(td, "error");
                    showToast(`Gagal menyimpan data ${nama}!`, "danger", delay_);
                    return false;
                }
            } catch (err) {
                hideLoading(td, "error");
                console.error(err);
                showToast(`Error: ${err.message}`, "danger", delay_);
                return false;
            }
        }


        function enableEdit(td) {
            if (td.querySelector("input")) return;

            const oldValue = td.innerText.trim();

            // SIMPAN NILAI AWAL
            td.dataset.original = oldValue;

            markUnsaved(td);

            td.innerHTML = "";

            const input = document.createElement("input");
            input.type = "time";
            input.className = "form-control form-control-sm";
            input.style.minWidth = "65px";

            if (oldValue !== "-" && /^\d{2}:\d{2}$/.test(oldValue)) {
                input.value = oldValue;
            }

            td.appendChild(input);
            input.focus();

            input.addEventListener("keydown", e => {
                if (e.key === "Enter") {
                    e.preventDefault();
                    input.blur();
                }
            });

            input.addEventListener("blur", () => {
                const newValue = input.value || "-";
                td.innerHTML = newValue;

                // ⛔ NILAI SAMA → BATAL SAVE
                if (newValue === oldValue) {
                    td.classList.add("saved-success");
                    setTimeout(() => td.classList.remove("saved-success"), 600);
                    clearUnsaved(td);
                    return;
                }


                // ✅ NILAI BEDA → SAVE
                if (document.getElementById("autoSave")?.checked && newValue !== "-") {
                    saveData(td);
                }
            }, {
                once: true
            });
        }


        async function saveAllData() {
            let cells = document.querySelectorAll("td[data-date]");
            let saved = 0;

            for (let td of cells) {
                const result = await saveData(td, 800);
                if (result) saved++;
            }

            showToast(`${saved} data berubah berhasil disimpan!`, "primary", 3000);

            hasUnsavedChanges = false;
            setTimeout(() => location.reload(), 1500);
        }


        // === SORTING ===
        let sortAsc = true;

        document.getElementById("toggleSort")?.addEventListener("click", function(e) {
            e.preventDefault();

            const table = document.querySelector("#table-container table");
            if (!table) {
                showToast("Tidak ada tabel untuk diurutkan", "warning");
                return;
            }

            const tbody = table.querySelector("tbody");
            const rows = Array.from(tbody.querySelectorAll("tr"));

            rows.sort((a, b) => {
                const nameA = a.cells[1]?.innerText.trim().toLowerCase() || "";
                const nameB = b.cells[1]?.innerText.trim().toLowerCase() || "";
                if (nameA < nameB) return sortAsc ? -1 : 1;
                if (nameA > nameB) return sortAsc ? 1 : -1;
                return 0;
            });

            rows.forEach((row, index) => {
                row.querySelector("td").innerText = index + 1;
                tbody.appendChild(row);
            });

            const icon = this.querySelector("i");
            icon.className = sortAsc ? "fas fa-sort-alpha-up" : "fas fa-sort-alpha-down";
            sortAsc = !sortAsc;

            showToast(`Data diurutkan ${sortAsc ? 'Z-A' : 'A-Z'}`, "primary", 2000);
        });

        // === DELETE MODAL ===
        let deleteLock = false;

        function openDeleteModal() {
            if (deleteLock) {
                Swal.fire({
                    icon: 'info',
                    title: 'Tunggu Sebentar',
                    text: 'Tunggu 5 menit sebelum mencoba lagi.',
                    confirmButtonText: 'Oke'
                });
                return;
            }

            const captchaCode = Math.floor(10000 + Math.random() * 90000).toString();

            Swal.fire({
                title: '<i class="fas fa-shield-alt"></i> Verifikasi CAPTCHA',
                html: `
            <p class="mb-3">Masukkan kode berikut untuk melanjutkan:</p>
            <div class="p-3 rounded mb-3" style="background: rgba(255, 255, 255, 0.03); border: 1px solid var(--border-color);">
                <b style="font-size: 28px; letter-spacing: 4px; user-select: none; font-family: monospace;">
                    ${captchaCode}
                </b>
            </div>
        `,
                icon: 'warning',
                input: 'text',
                inputPlaceholder: 'Ketik kode di atas',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-check"></i> Verifikasi',
                cancelButtonText: '<i class="fas fa-times"></i> Batal',
                preConfirm: (input) => {
                    if (input !== captchaCode) {
                        Swal.showValidationMessage('Kode CAPTCHA salah!');
                        deleteLock = true;
                        setTimeout(() => deleteLock = false, 300000);
                        return false;
                    }
                    return true;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: '<i class="fas fa-trash-alt"></i> Konfirmasi Penghapusan',
                        text: 'Pilih jenis penghapusan:',
                        icon: 'question',
                        showCancelButton: true,
                        showDenyButton: true,
                        confirmButtonColor: '#dc3545',
                        denyButtonColor: '#fd7e14',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: '<i class="fas fa-trash"></i> Hapus Semua + Gambar',
                        denyButtonText: '<i class="fas fa-database"></i> Hanya Data',
                        cancelButtonText: '<i class="fas fa-times"></i> Batal'
                    }).then((choice) => {
                        if (choice.isConfirmed) {
                            window.location.href = "{{ url('/admin/absensi/delete-all?with_image=1') }}";
                        } else if (choice.isDenied) {
                            window.location.href = "{{ url('/admin/absensi/delete-all?with_image=0') }}";
                        }
                    });
                }
            });
        }

        function openDeleteByPeriodModal() {
            Swal.fire({
                title: '<i class="fas fa-calendar-alt"></i> Hapus data per Bulan',
                html: `
                    <div class="text-start mb-3">
                        <label class="form-label small fw-bold">PILIH BULAN & TAHUN:</label>
                        <input type="text" id="deletePeriodPicker" class="form-control" placeholder="Pilih Bulan & Tahun..">
                    </div>
                    <div class="form-check form-switch text-start mb-3">
                        <input class="form-check-input" type="checkbox" id="deleteWithImage" style="cursor:pointer">
                        <label class="form-check-label ps-2" for="deleteWithImage" style="cursor:pointer">
                            Ikut hapus file gambar fisik?
                        </label>
                    </div>
                    <div class="alert alert-warning py-2 mb-0">
                        <small><i class="fas fa-exclamation-triangle me-1"></i> Data yang dihapus tidak dapat dikembalikan!</small>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-trash"></i> Hapus Data',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#dc3545',
                didOpen: () => {
                    flatpickr("#deletePeriodPicker", {
                        locale: "id",
                        plugins: [
                            new monthSelectPlugin({
                                shorthand: true,
                                dateFormat: "F-Y",
                                altFormat: "F Y",
                                theme: "dark"
                            })
                        ]
                    });
                },
                preConfirm: () => {
                    const fp = document.getElementById('deletePeriodPicker')._flatpickr;
                    const selectedDate = fp.selectedDates[0];
                    if (!selectedDate) {
                        Swal.showValidationMessage('Silakan pilih bulan dan tahun!');
                        return false;
                    }
                    const month = selectedDate.getMonth() + 1;
                    const year = selectedDate.getFullYear();
                    const withImage = document.getElementById('deleteWithImage').checked ? 1 : 0;
                    return { month, year, withImage };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const { month, year, withImage } = result.value;
                    const url = `{{ route('admin.deleteAbsensiByPeriod') }}?month=${month}&year=${year}&with_image=${withImage}`;
                    
                    Swal.fire({
                        title: 'Konfirmasi Akhir',
                        text: `Hapus semua data absensi bulan ${month} tahun ${year}?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Hapus!',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: '#dc3545',
                    }).then((confirm) => {
                        if (confirm.isConfirmed) {
                            window.location.href = url;
                        }
                    });
                }
            });
        }

        // === AUTO SORT ON LOAD ===
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById("toggleSort")?.click();
        });
    </script>
    <script>
        // =================== UNSAVED CHANGES GUARD ===================
        let hasUnsavedChanges = false;

        // --- DETECT EDIT ---
        function markUnsaved(td) {
            hasUnsavedChanges = true;
            td.dataset.unsaved = "1";
        }

        // --- CLEAR UNSAVED ---
        function clearUnsaved(td) {
            td.removeAttribute("data-unsaved");
            const remaining = document.querySelectorAll('td[data-unsaved="1"]');
            if (remaining.length === 0) {
                hasUnsavedChanges = false;
            }
        }

        // === PREVENT RELOAD / CLOSE TAB ===
        window.addEventListener("beforeunload", function(e) {
            if (!hasUnsavedChanges) return;
            e.preventDefault();
            e.returnValue = ""; // WAJIB agar browser munculin warning
        });

        // === PREVENT FORM SUBMIT ===
        document.getElementById("filterForm")?.addEventListener("submit", function(e) {
            if (!hasUnsavedChanges) return;

            e.preventDefault();
            Swal.fire({
                icon: "warning",
                title: "Perubahan Belum Disimpan",
                text: "Masih ada data yang belum disimpan. Lanjutkan dan buang perubahan?",
                showCancelButton: true,
                confirmButtonText: "Ya, lanjutkan",
                cancelButtonText: "Batal"
            }).then(res => {
                if (res.isConfirmed) {
                    hasUnsavedChanges = false;
                    this.submit();
                }
            });
        });

        // === PREVENT LINK CLICK (EXPORT / DELETE / NAVIGASI) ===
        document.querySelectorAll("a").forEach(link => {
            link.addEventListener("click", function(e) {
                if (!hasUnsavedChanges) return;

                e.preventDefault();
                const href = this.href;

                Swal.fire({
                    icon: "warning",
                    title: "Perubahan Belum Disimpan",
                    text: "Jika lanjut, perubahan akan hilang.",
                    showCancelButton: true,
                    confirmButtonText: "Lanjutkan",
                    cancelButtonText: "Batal"
                }).then(res => {
                    if (res.isConfirmed) {
                        hasUnsavedChanges = false;
                        window.location.href = href;
                    }
                });
            });
        });
    </script>

@endsection
