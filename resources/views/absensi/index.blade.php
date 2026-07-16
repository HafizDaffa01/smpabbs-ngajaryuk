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
                    <input type="text" id="alamat" name="alamat" class="form-control" placeholder="Lokasi akan muncul di sini" readonly required onclick="showLokasiPopup()" style="cursor: pointer;">
                    <input type="text" id="lokasi_dms" class="form-control mt-2 lokasi-dms-field" placeholder="Koordinat (DMS)" readonly onclick="showLokasiPopup()" style="cursor: pointer;">
                </div>
            </div>

            {{-- Foto Section --}}
            <div class="form-section">
                <label class="form-label">
                    <i class="fas fa-camera"></i> Foto
                </label>
                <div class="card card-modern">
                    <video id="cameraStream" autoplay muted playsinline class="camera-feed"></video>
                    <canvas id="cameraCanvas" style="display:none;"></canvas>
                    <input type="hidden" id="foto" name="foto" required>
                    <img id="previewFoto" src="" alt="Preview Foto" class="photo-preview" style="display:none;" onclick="showImagePopup()">
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
/* ===== RESPONSIVE CONTAINER ===== */
.attendance-container {
    display: flex;
    justify-content: center;
    background: var(--bg-body);
    padding: 15px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    width: 100%;
}

.attendance-card {
    width: 100%;
    max-width: 450px;
    background: var(--bg-card);
    border-radius: 16px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.4);
    overflow: hidden;
    animation: slideUp 0.3s ease-out;
    border: 1px solid var(--border-color);
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* ===== HEADER ===== */
.attendance-header {
    background: rgba(255, 255, 255, 0.03);
    color: var(--text-main);
    padding: 30px 20px;
    text-align: center;
    border-bottom: 1px solid var(--border-color);
}

.header-content {
    display: flex;
    justify-content: center;
    align-items: center;
}

.clock-section {
    display: flex;
    align-items: center;
    gap: 15px;
}

.clock-section i {
    font-size: 2.5rem;
    color: var(--primary-color);
    opacity: 0.8;
}

.time-display {
    text-align: left;
}

.clock-text {
    font-size: 2rem;
    font-weight: 800;
    letter-spacing: -0.5px;
    line-height: 1;
    color: var(--text-main);
}

.date-text {
    font-size: 0.85rem;
    color: var(--text-muted);
    margin-top: 5px;
}

/* ===== GREETING ===== */
.greeting-section {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 20px;
    background: rgba(255, 255, 255, 0.02);
    border-bottom: 1px solid var(--border-color);
}

.greeting-section i {
    font-size: 1.5rem;
    color: var(--primary-color);
}

.greeting-section h4 {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 600;
    color: var(--text-main);
}

/* ===== FORM SECTION ===== */
#absensiForm {
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-section {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.form-label {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--text-main);
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 5px;
}

.form-label i {
    color: var(--primary-color);
    font-size: 1.1rem;
}

.form-control {
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 12px 14px;
    font-size: 0.95rem;
    transition: all 0.2s ease;
    background: #0f172a;
    color: var(--text-main);
}

.form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    outline: none;
    background: #0f172a;
    color: var(--text-main);
}

.form-control-lg {
    min-height: 45px;
    font-size: 1rem;
}

/* ===== CARD MODERN ===== */
.card-modern {
    border: 1px solid var(--border-color);
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
    background: #0f172a;
}

.map-container {
    width: 100%;
    height: 200px;
    background: #0f172a;
    border-radius: 8px;
}

.camera-feed {
    width: 100%;
    height: 240px;
    background: #1F2937;
    border-radius: 8px;
    object-fit: cover;
}

.photo-preview {
    width: 100%;
    height: 240px;
    object-fit: cover;
    border-radius: 8px;
}

/* ===== BUTTONS ===== */
.buttons-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin: 10px 0;
}

.btn {
    border: none;
    border-radius: 8px;
    padding: 12px 20px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-lg {
    padding: 14px 20px;
    font-size: 1rem;
}

.btn-primary {
    background: var(--primary-color);
    color: white;
    border: 1px solid var(--primary-color);
}

.btn-primary:hover {
    background: var(--primary-hover);
    border-color: var(--primary-hover);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.btn-secondary {
    background: #475569;
    color: white;
    border: 1px solid #475569;
}

.btn-secondary:hover {
    background: #334155;
    border-color: #334155;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(107, 114, 128, 0.4);
}

.btn-success {
    background: var(--success-color);
    color: white;
    border: 1px solid var(--success-color);
}

.btn-success:hover {
    background: #059669;
    border-color: #059669;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
}

.btn-icon i {
    font-size: 1.1rem;
}

.w-100 {
    width: 100%;
}

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

/* ===== MOBILE RESPONSIVE ===== */
@media (max-width: 480px) {
    .attendance-container {
        padding: 4px;
        min-height: auto;
    }

    .attendance-card {
        max-width: 100%;
        border-radius: 10px;
    }

    .attendance-header {
        padding: 7px 10px;
    }

    .clock-section {
        gap: 8px;
    }

    .clock-section i {
        font-size: 1.44rem;
    }

    .clock-text {
        font-size: 1.24rem;
    }

    .date-text {
        font-size: 0.64rem;
        margin-top: 1px;
    }

    .greeting-section {
        padding: 5px 10px;
    }

    .greeting-section i {
        font-size: 0.94rem;
    }

    .greeting-section h4 {
        font-size: 0.84rem;
    }

    #absensiForm {
        padding: 8px;
        gap: 6px;
    }

    .form-section {
        gap: 2px;
    }

    .form-label {
        font-size: 0.77rem;
        margin-bottom: 0;
    }

    .form-control {
        padding: 6px 8px;
        font-size: 0.81rem;
    }

    .form-control-lg {
        min-height: 34px;
        font-size: 0.86rem;
    }

    .map-container {
        height: 99px;
    }

    .camera-feed,
    .photo-preview {
        height: 149px;
    }

    .buttons-container {
        gap: 6px;
        margin: 0;
    }

    .btn {
        padding: 7px 7px;
        font-size: 0.76rem;
    }

    .btn-lg {
        padding: 8px 9px;
        font-size: 0.81rem;
    }

    .btn-icon i {
        font-size: 0.86rem;
    }

    .lokasi-dms-field {
        display: none;
    }

    #notification-container {
        top: 4px;
        right: 4px;
        left: 4px;
    }

    .notification {
        min-width: auto;
        width: 100%;
        padding: 6px 8px;
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
                <p style="text-align:left; margin-bottom:10px;">
                    <strong>Alamat:</strong><br>${alamat}<br><br>
                    <strong>Koordinat (DMS):</strong><br>${lokasiDMS}<br><br>
                    <div id="popupMap" style="width:100%; height:300px; border:1px solid #ccc; border-radius:6px;"></div>
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
