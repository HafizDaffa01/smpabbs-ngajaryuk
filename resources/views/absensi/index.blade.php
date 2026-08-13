@extends('layouts.app')

@section('content')


<div class="attendance-container">
    <div class="attendance-card">
        {{-- Header dengan Waktu & Tanggal --}}
        <div class="attendance-header">
            <div class="header-content">
                <div class="clock-section">
                    <i class="fas fa-clock"></i>
                    <div class="time-display">
                        <div id="clock" class="clock-text"></div>
                        <div id="date" class="date-text"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Greeting --}}
        <div class="greeting-section">
            <i class="fas fa-hand-wave"></i>
            <h4 id="greeting"></h4>
        </div>

        <form id="absensiForm" action="{{ route('absensi.store') }}" method="POST">
            @csrf

            {{-- Nama Guru (untuk non-auth) --}}
            @auth
            @else
                <div class="form-section">
                    <label class="form-label">
                        <i class="fas fa-user"></i> Pilih Nama Anda
                    </label>
                    <select name="nama" class="form-control form-control-lg" required>
                        <option value="" disabled selected>-- Pilih Nama --</option>
                        @foreach($guruList as $guru)
                            <option value="{{ $guru }}">{{ $guru }}</option>
                        @endforeach
                    </select>
                </div>
            @endauth

            {{-- Lokasi Section --}}
            <div class="form-section">
                <label class="form-label">
                    <i class="fas fa-map-marker-alt"></i> Lokasi
                </label>
                <div class="card card-modern">
                    <div id="map" class="map-container"></div>
                    <input type="hidden" id="lokasi" name="lokasi" required>
                    <input type="text" id="alamat" name="alamat" class="form-control cursor-pointer" placeholder="Lokasi akan muncul di sini" readonly required onclick="showLokasiPopup()">
                    <input type="text" id="lokasi_dms" class="form-control mt-2 lokasi-dms-field cursor-pointer" placeholder="Koordinat (DMS)" readonly onclick="showLokasiPopup()">
                </div>
            </div>

            {{-- Foto Section --}}
            <div class="form-section">
                <label class="form-label">
                    <i class="fas fa-camera"></i> Foto
                </label>
                <div class="card card-modern">
                    <video id="cameraStream" autoplay muted playsinline class="camera-feed"></video>
                    <canvas id="cameraCanvas" class="d-none"></canvas>
                    <input type="hidden" id="foto" name="foto" required>
                    <img id="previewFoto" src="" alt="Preview Foto" class="photo-preview d-none" onclick="showImagePopup()">
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="buttons-container">
                <button type="button" class="btn btn-primary btn-lg btn-icon" onclick="getLocation()">
                    <i class="fas fa-location-crosshairs"></i> Ambil Lokasi
                </button>
                <button type="button" id="btnCapture" class="btn btn-secondary btn-lg btn-icon" onclick="togglePhoto()">
                    <i class="fas fa-camera"></i> Ambil Foto
                </button>
            </div>

            {{-- Submit Button --}}
            <button type="submit" class="btn btn-success btn-lg w-100">
                <i class="fas fa-paper-plane"></i> Kirim Absensi
            </button>
        </form>
    </div>
</div>

<div id="notification-container"></div>

<style>
/* ===== NOTIFICATION ===== */
#notification-container {
    position: fixed;
    top: 20px;
    right: 20px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    z-index: 9999;
}

.notification {
    background: #10B981;
    color: white;
    padding: 14px 18px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
    opacity: 0;
    transform: translateX(400px);
    transition: all 0.3s ease-in-out;
    font-size: 0.95rem;
    min-width: 250px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.notification.show {
    opacity: 1;
    transform: translateX(0);
}

.notification.error {
    background: #EF4444;
}

.notification.warning {
    background: #F59E0B;
    color: white;
}

.notification::before {
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    font-size: 1.1rem;
}

.notification.success::before {
    content: '\f058';
}

.notification.error::before {
    content: '\f057';
}

.notification.warning::before {
    content: '\f071';
}

@media (max-width: 480px) {
    #notification-container {
        top: 4px;
        right: 4px;
        left: 4px;
    }

    .notification {
        min-width: auto;
        width: 100%;
        padding: 6px 8px;
    }
}
</style>
        font-size: 0.77rem;
    }
}

@media (max-width: 360px) {
    .attendance-container {
        padding: 2px;
    }

    .attendance-header {
        padding: 6px 8px;
    }

    .clock-section i {
        font-size: 1.2rem;
    }

    .clock-text {
        font-size: 1.1rem;
    }

    .greeting-section {
        padding: 5px 8px;
    }

    .greeting-section h4 {
        font-size: 0.78rem;
    }

    #absensiForm {
        padding: 7px;
        gap: 5px;
    }

    .map-container {
        height: 80px;
    }

    .camera-feed,
    .photo-preview {
        height: 120px;
    }

    .btn {
        padding: 6px 6px;
        font-size: 0.72rem;
    }

    .btn-lg {
        padding: 7px 8px;
        font-size: 0.77rem;
    }
}
</style>

<script>
// === Koordinat Target SMP ABBS ===
const targetLat = -7.5391338;
const targetLon = 110.805751;
const radius = 30; // meter

let isInsideRadius = false;

// === Custom Marker Icons ===
const greenIcon = L.icon({
    iconUrl: 'https://cdn.rawgit.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
    iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34]
});
const blueIcon = L.icon({
    iconUrl: 'https://cdn.rawgit.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
    iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34]
});
const redIcon = L.icon({
    iconUrl: 'https://cdn.rawgit.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
    iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34]
});

// === Inisialisasi Map ===
const map = L.map('map').setView([targetLat, targetLon], 18);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '' }).addTo(map);

// Lingkaran radius
const targetCircle = L.circle([targetLat, targetLon], {
    color: 'green', fillColor: '#0f0', fillOpacity: 0.2, radius: radius
}).addTo(map).bindPopup("SMP ABBS Surakarta").openPopup();

let userMarker;

// === Notifikasi Toast ===
function showNotification(msg, type="success") {
    const container = document.getElementById("notification-container");
    const notif = document.createElement("div");
    notif.className = `notification ${type}`;
    notif.innerText = msg;
    container.appendChild(notif);
    setTimeout(() => notif.classList.add("show"), 100);
    setTimeout(() => { notif.classList.remove("show"); setTimeout(() => notif.remove(), 300); }, 4000);
}

// === Rumus Haversine ===
function getDistance(lat1, lon1, lat2, lon2) {
    const R = 6371000;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat / 2) ** 2 +
        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
        Math.sin(dLon / 2) ** 2;
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
}

// === Ambil Lokasi ===
function getLocation() {
    showNotification("Sedang mengambil lokasi Anda...", "warning");
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(success, error, { enableHighAccuracy: true });
    } else {
        showNotification("Browser Anda tidak mendukung Geolocation.", "error");
    }
}

function success(position) {
    const lat = position.coords.latitude;
    const lon = position.coords.longitude;

    document.getElementById("lokasi").value = `${lat},${lon}`;

    const dmsLat = toDMS(lat,true);
    const dmsLon = toDMS(lon,false);
    document.getElementById("lokasi_dms").value = `${dmsLat}, ${dmsLon}`;

    const distance = getDistance(lat, lon, targetLat, targetLon);
    isInsideRadius = distance <= radius;

    if (userMarker) userMarker.remove();
    const icon = isInsideRadius ? blueIcon : redIcon;
    userMarker = L.marker([lat, lon], { icon }).addTo(map)
        .bindPopup(`Posisi Anda (${distance.toFixed(2)} m dari SMP ABBS)`).openPopup();
    map.setView([lat, lon], 18);

    fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lon}&format=json&accept-language=id`)
        .then(r => r.json())
        .then(data => document.getElementById("alamat").value = data.display_name || "Alamat tidak ditemukan")
        .catch(()=>document.getElementById("alamat").value="Alamat tidak ditemukan");

    if (isInsideRadius) {
        showNotification("Anda berada dalam area absensi SMP ABBS Surakarta", "success");
        Swal.fire({
            icon: 'success',
            title: 'Berhasil',
            text: `Anda berada di area absensi (${distance.toFixed(2)} meter dari titik sekolah)`
        });
    } else {
        showNotification("Anda berada di luar area absensi", "error");
        Swal.fire({
            icon: 'warning',
            title: 'Di Luar Area SMP ABBS',
            text: `Mohon maaf, Anda saat ini berada di luar radius area SMP ABBS Surakarta. 
Silakan mendekat ke lokasi sekolah untuk melakukan absensi dengan benar.`,
        });
    }
}

function error(err) {
    showNotification("Gagal mengambil lokasi: " + err.message, "error");
}

function toDMS(deg, isLat) {
    const absolute = Math.abs(deg);
    const degrees = Math.floor(absolute);
    const minutesNotTruncated = (absolute - degrees) * 60;
    const minutes = Math.floor(minutesNotTruncated);
    const seconds = ((minutesNotTruncated - minutes) * 60).toFixed(1);
    const direction = isLat ? (deg >=0 ? "N":"S") : (deg>=0 ? "E":"W");
    return `${degrees}°${minutes}'${seconds}"${direction}`;
}

let stream;
let isPhotoTaken = false;

function startCamera(){
    navigator.mediaDevices.getUserMedia({video:true})
        .then(s=>{ stream=s; document.getElementById("cameraStream").srcObject=stream; })
        .catch(err => showNotification("Tidak bisa membuka kamera: "+err.message,"error"));
}

function stopCamera(){
    if(stream){ stream.getTracks().forEach(track=>track.stop()); stream=null; }
}

function togglePhoto(){
    const video=document.getElementById("cameraStream");
    const canvas=document.getElementById("cameraCanvas");
    const preview=document.getElementById("previewFoto");
    const fotoInput=document.getElementById("foto");
    const btn=document.getElementById("btnCapture");

    if(!isPhotoTaken){
        canvas.width=video.videoWidth; canvas.height=video.videoHeight;
        canvas.getContext("2d").drawImage(video,0,0,canvas.width,canvas.height);
        const dataUrl=canvas.toDataURL("image/png");
        fotoInput.value=dataUrl;
        preview.src=dataUrl; preview.style.display="block"; video.style.display="none";
        btn.innerText="Ambil Foto Lagi"; isPhotoTaken=true; stopCamera();
        showNotification("Foto berhasil diambil","success");
    } else {
        video.style.display="block"; preview.style.display="none";
        fotoInput.value=""; btn.innerText="Ambil Foto"; isPhotoTaken=false; startCamera();
    }
}

window.addEventListener("focus", ()=>{if(!isPhotoTaken) startCamera();});
window.addEventListener("blur", stopCamera);
window.onload=startCamera;

// === Validasi sebelum submit ===
document.getElementById('absensiForm').addEventListener('submit', function(e) {
    const lokasi = document.getElementById('lokasi').value.trim();
    const alamat = document.getElementById('alamat').value.trim();
    const foto = document.getElementById('foto').value.trim();

    if (!lokasi || !alamat || !foto) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Data Belum Lengkap',
            text: 'Mohon pastikan lokasi, alamat, dan foto sudah diambil sebelum mengirim absensi.'
        });
        return;
    }

    if (!isInsideRadius) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Di Luar Area Sekolah',
            text: 'Mohon maaf, Anda tidak dapat melakukan absensi karena berada di luar area SMP ABBS Surakarta.'
        });
        return;
    }
});

// === Jam & Tanggal ===
function updateClock(){
    const now=new Date();
    document.getElementById('clock').innerText=now.toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit',second:'2-digit'}).replace(/\./g, ':');
    document.getElementById('date').innerText=now.toLocaleDateString('id-ID',{weekday:'long',year:'numeric',month:'long',day:'numeric'});
}
function updateGreeting(){
    const hour=new Date().getHours();
    let greet = hour<11 ? "Selamat pagi" : hour<15 ? "Selamat siang" : hour<18 ? "Selamat sore" : "Selamat malam";
    document.getElementById('greeting').innerText = `${greet}, {{ Auth::user()->name ?? 'Guru' }}`;
}
setInterval(updateClock,1000);
updateClock(); updateGreeting();
</script>
<script>
function showImagePopup() {
    const foto = document.getElementById('foto').value;
    if (foto) {
        Swal.fire({
            imageUrl: foto,
            imageAlt: 'Foto Absensi',
            confirmButtonText: 'Tutup'
        });
    } else {
        Swal.fire({
            icon: 'info',
            title: 'Belum Ada Foto',
            text: 'Silakan ambil foto terlebih dahulu.',
        });
    }
}
function showLokasiPopup() {
    const alamat = document.getElementById('alamat').value;
    const lokasiDMS = document.getElementById('lokasi_dms').value;
    const lokasi = document.getElementById('lokasi').value;

    if (alamat && lokasiDMS && lokasi) {
        Swal.fire({
            title: 'Detail Lokasi Anda',
            html: `
                <p class="text-start mb-2">
                    <strong>Alamat:</strong><br>${alamat}<br><br>
                    <strong>Koordinat (DMS):</strong><br>${lokasiDMS}<br><br>
                    <div id="popupMap" class="map-container"></div>
                </p>
            `,
            width: 600,
            didOpen: () => {
                const [lat, lon] = lokasi.split(',').map(Number);

                const popupMap = L.map('popupMap').setView([targetLat, targetLon], 17);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: ''
                }).addTo(popupMap);

                const targetMarkerPopup = L.marker([targetLat, targetLon], {
                    icon: L.icon({
                        iconUrl: greenIcon.options.iconUrl,
                        iconSize: [30, 30]
                    })
                }).addTo(popupMap)
                    .bindPopup("SMP ABBS Surakarta");

                const targetCirclePopup = L.circle([targetLat, targetLon], {
                    color: 'green',
                    fillColor: '#0f0',
                    fillOpacity: 0.2,
                    radius: radius
                }).addTo(popupMap);

                const distance = getDistance(lat, lon, targetLat, targetLon);

                const userIcon = L.icon({
                    iconUrl: distance <= radius
                        ? blueIcon.options.iconUrl
                        : redIcon.options.iconUrl,
                    iconSize: [30, 30]
                });

                L.marker([lat, lon], { icon: userIcon })
                    .addTo(popupMap)
                    .bindPopup("Posisi Anda");

                const group = L.featureGroup([
                    targetMarkerPopup,
                    L.marker([lat, lon])
                ]);
                popupMap.fitBounds(group.getBounds(), { padding: [20, 20] });
            },
            confirmButtonText: 'Tutup'
        });
    } else {
        Swal.fire({
            icon: 'info',
            title: 'Belum Ada Lokasi',
            text: 'Silakan ambil lokasi terlebih dahulu sebelum melihat detail lokasi.',
        });
    }
}

</script>
@endsection
