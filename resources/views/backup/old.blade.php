@extends('layouts.app')

@section('title', 'Backup Dashboard')

@section('content')
<div class="container">
    <h1 class="mb-4">Backup Panel</h1>

    {{-- =================== FILTER =================== --}}
    <form method="get" id="filterForm" action="{{ route('backup.index') }}" 
        class="mb-3 d-flex flex-wrap gap-2">

        {{-- Bulan --}}
        <select name="month" class="form-select" onchange="this.form.submit()">
            <option value="" selected disabled>-- Pilih Bulan --</option>
            @foreach($months as $key => $label)
                <option value="{{ $key }}" {{ $filterMonth==$key ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>

        {{-- Tahun --}}
        <select name="year" class="form-select" onchange="this.form.submit()">
            <option value="" selected disabled>-- Pilih Tahun --</option>
            @foreach($years as $y)
                <option value="{{ $y }}" {{ $filterYear==$y ? 'selected' : '' }}>
                    {{ $y }}
                </option>
            @endforeach
        </select>

        {{-- Tipe Data --}}
        <select name="data_type" class="form-select" onchange="this.form.submit()">
            <option value="" selected disabled>-- Pilih Tipe Data --</option>
            <option value="waktu"  {{ $dataType==='waktu'  ? 'selected' : '' }}>Waktu</option>
            <option value="lokasi" {{ $dataType==='lokasi' ? 'selected' : '' }}>Lokasi</option>
            <option value="gambar" {{ $dataType==='gambar' ? 'selected' : '' }}>Gambar</option>
        </select>

        {{-- Search --}}
        <input type="text" name="search" class="form-control" placeholder="Cari..." 
            value="{{ $search }}" class="flex-fill min-w-200">

        <button type="submit" class="btn btn-primary">Search</button>

        {{-- Tombol Scan (khusus waktu) --}}
        @if($dataType === 'waktu')
            <button type="button" onclick="saveAllData()" class="btn btn-success">
                Scan & Simpan Perubahan
            </button>
        @endif

        {{-- Switch Auto Save --}}
        <div class="autosave-container"  @if ($dataType !== "waktu") hidden @endif>
            <label class="switch" title="Auto Save Toggle">
                <input type="checkbox" id="autoSave">
                <span class="slider"><span class="knob"></span></span>
            </label>
        </div>

    </form>

    {{-- =================== TABEL WAKTU =================== --}}
    <div id="table-container">
        @if($dataType === 'waktu' && isset($days) && isset($gridData))
        <div class="table-wrapper overflow-auto">
            <table class="table table-bordered align-middle text-center">
                <thead class="table-dark">
                    <tr>
                        <th rowspan="2" class="sticky-col sticky-header" style="left:0; z-index:3;">No</th>
                        <th rowspan="2" class="sticky-col sticky-header" style="left:50px; z-index:3;">Nama</th>
                        <th colspan="{{ count($days) }}" class="sticky-header">
                            {{ $months[$filterMonth] ?? '' }}
                        </th>
                        <th rowspan="2" class="sticky-col-right sticky-header" style="right:0; z-index:3;">TOT</th>
                    </tr>
                    <tr>
                        @foreach($days as $d)
                            <th class="sticky-header">
                                {{ $d->format('d') }} <br>
                                <span class="text-xs">{{ $d->translatedFormat('F') }}</span>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @php $no = 1; @endphp
                    @foreach($gridData as $nama => $row)
                        @php $tot = 0; @endphp
                        <tr data-nama="{{ $nama }}" data-unit="{{ $row['unit'] ?? '' }}">
                            <td class="sticky-col" style="left:0;">{{ $no++ }}</td>
                            <td class="sticky-col" style="left:50px;">{{ $nama }}</td>
                            @foreach($days as $d)
                                @php
                                    $tgl = $d->format('Y-m-d');
                                    $val = $row['data'][$tgl] ?? '-';
                                    if($val !== '-' && $val <= '06:50') $tot++;
                                @endphp
                                <td contenteditable="false" data-date="{{ $tgl }}"
                                    onclick="enableEdit(this)">
                                    {{ $val }}
                                </td>
                            @endforeach
                            <td class="sticky-col-right" style="right:0;">{{ $tot }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- =================== TABEL LOKASI =================== --}}
    @if($dataType === 'lokasi')
    <div class="table-wrapper overflow-auto">
        <table class="table table-striped table-bordered align-middle text-center">
            <thead class="table-dark">
                <tr>
                    <th class="sticky-col sticky-header" style="left:0; z-index:3;">#</th>
                    <th class="sticky-col sticky-header" style="left:50px; z-index:3;">Nama</th>
                    <th class="sticky-header">Tanggal</th>
                    <th class="sticky-col-right sticky-header" style="right:0; z-index:3;">Alamat</th>
                </tr>
            </thead>
            <tbody>
                @foreach($absensis as $i => $absen)
                <tr>
                    <td class="sticky-col" style="left:0;">{{ $i+1 }}</td>
                    <td class="sticky-col" style="left:50px;">{{ $absen->nama }}</td>

                    {{-- Kolom Tanggal --}}
                    <td>
                        @if($absen->waktu)
                            {{ \Carbon\Carbon::parse($absen->waktu)->format('d-m-Y - H:i') }}
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>

                    {{-- Kolom Alamat --}}
                    <td>
                        @if($absen->lokasi)
                            <a href="#"
                            onclick="event.preventDefault(); showMap('{{ $absen->lokasi }}', '{{ $absen->nama }}', '{{ $absen->waktu }}');"
                            style="color: inherit; text-decoration: none;">
                                {{ trim(explode(',', $absen->alamat ?? '-')[0]) }}
                            </a>
                        @else
                            <span class="text-muted">Tidak ada</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif


    {{-- =================== TABEL GAMBAR =================== --}}
    @if($dataType === 'gambar')
    <div style="overflow-x:auto; max-width:100%;" class="table-wrapper">
        <table class="table table-striped table-bordered align-middle text-center">
            <thead class="table-dark">
                <tr>
                    <th class="sticky-header">#</th>
                    <th class="sticky-header">Nama</th>
                    <th class="sticky-col-right sticky-header" style="right:0;">Foto</th>
                </tr>
            </thead>
            <tbody>
                @foreach($absensis as $i => $absen)
                <tr>
                    <td>{{ $i+1 }}</td>
                    <td>{{ $absen->nama }}</td>
                    <td class="sticky-col-right" style="right:0;">
                        @if($absen->foto)
                            {{-- <img src="{{ asset('public/' . $absen->foto) }}" width="80" class="img-thumbnail"
                                onclick='Swal.fire({
                                    title: "{{ $absen->nama }}",
                                    text: "@ {{ parse_url(asset('public/' . $absen->foto), PHP_URL_PATH) }}",
                                    imageUrl: "{{ asset('public/' . $absen->foto) }}",
                                    imageHeight: 200
                                });'> --}}

                            <img src="{{ asset($absen->foto) }}" width="80" class="img-thumbnail"
                                onclick='Swal.fire({
                                    title: "{{ $absen->nama }}",
                                    text: "@ {{ parse_url(asset($absen->foto), PHP_URL_PATH) }}",
                                    imageUrl: "{{ asset($absen->foto) }}",
                                    imageHeight: 200
                                });'>
                        @else
                            <span class="text-muted">Tidak ada</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- =================== BACKUP & DELETE =================== --}}
    <button type="button" class="btn btn-danger mt-3" onclick="openDeleteModal()">
        Hapus SEMUA Absensi
    </button>

    <a href="{{ route('backup.export' . ucfirst($dataType), [
        'month' => $filterMonth,
        'year' => $filterYear,
        'search' => $search,
    ]) }}" class="btn btn-primary mt-3">
        Backup
    </a>
</div>

{{-- =================== NOTIFIKASI =================== --}}
<div id="notification-container"></div>

{{-- =================== STYLE =================== --}}
<style>
/* --- TABEL --- */
td:nth-child(2), th:nth-child(2) { min-width: 200px; max-width: 700px; }
.saving        { background:#cde9ff !important; outline:2px solid #0999ec; }
.saved-success { background:#ddffcd !important; outline:2px solid #198754; }
.saved-error   { background:#ffcdcd !important; outline:2px solid #dc3545; }
.editing       { background:#fff3cd !important; outline:2px solid #ffc107; }

/* --- NOTIFIKASI --- */
#notification-container {
    position:fixed; top:20px; right:20px;
    display:flex; flex-direction:column; gap:10px; z-index:9999;
}
.notification {
    padding:12px 20px; border-radius:6px;
    box-shadow:0 4px 6px rgba(0,0,0,0.2);
    opacity:0; transform:translateX(100%);
    transition:all 0.3s ease-in-out;
    font-size:0.95rem; color:white;
    display:inline-block; width:auto;
}
.notification.show { opacity:1; transform:translateX(0); }
.notif-success { background:#198754; }
.notif-danger  { background:#dc3545; }
.notif-warning { background:#ffc107; color:#000; }
.notif-primary { background:#0d6efd; }

/* --- STICKY TABEL --- */
.sticky-col { position:sticky; background:inherit; }
.sticky-col-right { position:sticky; background:inherit; }
.sticky-header { position:sticky; top:0; background:inherit; z-index:2; }

/* --- SWITCH AUTOSAVE --- */
.autosave-container { display:flex; flex-direction:column; align-items:center; gap:5px; }
.switch { position:relative; display:inline-block; width:60px; height:34px; }
.switch input { opacity:0; width:0; height:0; }
.slider { position:absolute; cursor:pointer; top:0; left:0; right:0; bottom:0;
    background:#ccc; transition:.2s; border-radius:8px; }
.knob { position:absolute; height:26px; width:26px; left:4px; bottom:4px;
    background:#fff; transition:.2s; border-radius:6px; }
.switch input:checked + .slider { background:#2196F3; }
.switch input:checked + .slider .knob { transform:translateX(26px); }
</style>

{{-- =================== SCRIPT =================== --}}
<script>
function postWithQuery(url, params = {}) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = url;
    const csrf = document.querySelector('meta[name="csrf-token"]');
    if (csrf) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = '_token';
        input.value = csrf.getAttribute('content');
        form.appendChild(input);
    }
    for (const [key, value] of Object.entries(params)) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value;
        form.appendChild(input);
    }
    document.body.appendChild(form);
    form.submit();
}

function showMap(lokasi, nama, waktu) {
    const [lat, lon] = lokasi.split(',').map(x => parseFloat(x.trim()));
    Swal.fire({
        title: `${nama}`,
        html: `
          <p style="margin:4px 0;">Waktu absen: <b>${waktu}</b></p>
          <footnote style="font-size:10px;color:#666;">Format: "YYYY-MM-DD HH:MM:SS"</footnote>
          <div id="mapPrev" style="width:100%;height:300px;"></div>
        `,
        didOpen: () => {
            const map = L.map('mapPrev').setView([lat, lon], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '' }).addTo(map);
            L.marker([lat, lon]).addTo(map).bindPopup(`<b>${nama}</b>`).openPopup();
        },
        width: 600,
        showCloseButton: true,
        showConfirmButton: false
    });
}

function showToast(message, type="success", delay=2000) {
    const container = document.getElementById("notification-container");
    const notif = document.createElement("div");
    notif.className = `notification notif-${type}`;
    notif.innerText = message;
    container.appendChild(notif);
    setTimeout(() => notif.classList.add("show"), 100);
    setTimeout(() => { notif.classList.remove("show"); setTimeout(() => notif.remove(), 300); }, delay);
}

function showLoading(td) { td.classList.add("saving"); }
function hideLoading(td, status=null) {
    td.classList.remove("saving");
    if(status === "success") {
        td.classList.add("saved-success");
        setTimeout(() => td.classList.remove("saved-success"), 1500);
    } else if(status === "error") {
        td.classList.add("saved-error");
        setTimeout(() => td.classList.remove("saved-error"), 1500);
    }
}
async function saveData(td, delay_=2000) {
    let row = td.closest("tr");
    let nama = row.dataset.nama;
    let unit = row.dataset.unit;
    let date = td.getAttribute("data-date");
    let value = td.innerText.trim() || "-";
    if(value === "-") return;

    try {
        showLoading(td);
        const res = await fetch("{{ route('backup.save') }}", {
            method: "POST",
            headers: { "X-CSRF-TOKEN": "{{ csrf_token() }}", "Content-Type": "application/json" },
            body: JSON.stringify({ nama, unit, date, value, data_type:'waktu' })
        });
        const json = await res.json();
        if(res.ok) {
            td.innerText = json.value ?? value;
            hideLoading(td, "success");
            showToast(`Data ${nama} pada ${date} berhasil disimpan!`, "success", delay_);

            // === AUTO RELOAD JIKA AUTOSAVE ===
            if(document.getElementById("autoSave").checked){
                setTimeout(()=> location.reload(), 500); // delay sebentar biar notif terlihat
            }

        } else {
            hideLoading(td, "error");
            showToast(`Gagal menyimpan data ${nama} (${date})!`, "danger", delay_);
        }
    } catch(err) {
        hideLoading(td, "error");
        console.error("Save error:", err);
        showToast(`Terjadi error saat menyimpan ${nama} (${date})!`, "danger", delay_);
    }
}


function enableEdit(td) {
    td.contentEditable = true;
    td.classList.add("editing");
    td.focus();
    td.addEventListener("blur", ()=>{
        td.contentEditable = false; td.classList.remove("editing"); td.classList.add("saving");
        if(document.getElementById("autoSave").checked) saveData(td);
    }, {once:true});
}

async function saveAllData() {
    let cells = document.querySelectorAll("td[data-date]");
    for (let td of cells) { await saveData(td, 1000); }
    showToast("Semua data berhasil disimpan!", "primary", 3000);
    location.reload();
}

/* --- AUTO SAVE --- */
let autoSaveCheckbox = document.getElementById("autoSave");
document.querySelectorAll("td[data-date]").forEach(td=>{
    td.addEventListener("keydown", (e)=>{
        if(e.key === "Enter") { e.preventDefault(); td.blur(); }
    });
});

let deleteLock = false;

function openDeleteModal() {
    if(deleteLock){
        Swal.fire({icon:'info',title:'Tunggu Sebentar',text:'Tunggu 5 menit sebelum mencoba lagi.',confirmButtonText:'Oke'});
        return;
    }

    const captchaCode = Math.floor(10000 + Math.random() * 90000).toString();

    Swal.fire({
        title: 'Tindakan Dibatasi',
        html: `Silakan isi CAPTCHA berikut sebelum menghapus semua absensi:<br><br>
               <b style="font-size: 24px; letter-spacing: 3px; user-select: none;">${captchaCode}</b>`,
        icon: 'warning',
        input: 'number',
        inputPlaceholder: 'Masukkan kode di atas',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Verifikasi',
        cancelButtonText: 'Batal',
        preConfirm: (input) => {
            if(input !== captchaCode){
                Swal.showValidationMessage('Kode CAPTCHA tidak cocok');
                deleteLock = true;
                setTimeout(()=>deleteLock=false,300000);
            }
            return true;
        }
    }).then((result)=>{
        if(result.isConfirmed){
            Swal.fire({
                title:'Konfirmasi Penghapusan',
                text:'Apakah kamu ingin menghapus semua absensi beserta gambar?',
                icon:'question',
                showCancelButton:true,
                confirmButtonColor:'#d33',
                cancelButtonColor:'#6c757d',
                confirmButtonText:'Ya, hapus dengan gambar',
                cancelButtonText:'Hanya data absensi'
            }).then((choice)=>{
                if(choice.isConfirmed){
                    Swal.fire({icon:'success',title:'Menghapus...',text:'Semua data absensi dan gambar sedang dihapus...',showConfirmButton:false,timer:1500});
                    postWithQuery('{{ url("/admin/absensi/delete-all") }}', {with_image: 1});
                } else if(choice.dismiss===Swal.DismissReason.cancel){
                    Swal.fire({icon:'success',title:'Menghapus...',text:'Semua data absensi (tanpa gambar) sedang dihapus...',showConfirmButton:false,timer:1500});
                    postWithQuery('{{ url("/admin/absensi/delete-all") }}', {with_image: 0});
                }
            });
        } else if(result.dismiss === Swal.DismissReason.cancel){
            Swal.fire({icon:'info',title:'Dibatalkan',text:'Tindakan penghapusan dibatalkan.',confirmButtonText:'Oke'});
        } else {
            Swal.fire({icon:'error',title:'CAPTCHA Salah',text:'Tunggu 5 menit untuk mencoba lagi.',confirmButtonText:'Oke'});
            deleteLock=true;
            setTimeout(()=>deleteLock=false,300000);
        }
    });
}
</script>
@endsection
