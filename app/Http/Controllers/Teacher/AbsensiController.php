<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Absensi;
use App\Models\Schedule;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Teacher;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

use Illuminate\Support\Facades\Log;

class AbsensiController extends Controller
{
    /**
     * Display the absensi form page.
     */
    public function index()
    {
        $guruList = Teacher::all()->toArray();

        return view('absensi.index', compact('guruList'));
    }

    /**
     * Validate and store new absensi record.
     */
    public function store(Request $request)
    {
        // 1. Validasi Request
        $request->validate([
            'lokasi' => 'required|string',
            'foto'   => 'required|string',
            'alamat' => 'nullable|string',
            'lat'    => 'nullable|numeric|between:-90,90',
            'lon'    => 'nullable|numeric|between:-180,180',
        ]);

        $user = Auth::user();

        // 1b. Server-side geofence validation
        if ($request->filled('lat') && $request->filled('lon')) {
            // SMP ABBS Surakarta approximate coordinates: -7.5564, 110.8347
            $schoolLat = -7.5564;
            $schoolLon = 110.8347;
            $maxRadius = 1000; // meters

            $distance = $this->haversineDistance($request->lat, $request->lon, $schoolLat, $schoolLon);
            if ($distance > $maxRadius) {
                return back()->with('error', 'Anda berada di luar radius sekolah. Presensi ditolak.');
            }
        }

        // 2. Cegah Absensi Ganda pada Hari yang Sama (atomic check)
        $alreadyCheckedIn = Absensi::where('user_id', $user->id)
            ->whereDate('waktu', now()->toDateString())
            ->first();

        if ($alreadyCheckedIn) {
            $jamAbsen = Carbon::parse($alreadyCheckedIn->waktu)->format('H:i');
            return redirect()->route('error')->with('error', "Anda sudah presensi pada jam {$jamAbsen}");
        }

        // 3. Proses File Base64
        $fotoName = null;
        if ($request->filled('foto')) {
            try {
                $fotoData = preg_replace('/^data:image\/\w+;base64,/', '', $request->foto);
                $dFoto = base64_decode($fotoData);
                
                if ($dFoto !== false) {
                    // Validate decoded photo size (max 5MB)
                    if (strlen($dFoto) > 5 * 1024 * 1024) {
                        return back()->with('error', 'Ukuran foto terlalu besar (maksimal 5MB).');
                    }

                    // Validate MIME type
                    $finfo = new \finfo(FILEINFO_MIME_TYPE);
                    $mimeType = $finfo->buffer($dFoto);
                    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    if (!in_array($mimeType, $allowedMimes)) {
                        return back()->with('error', 'Format foto tidak valid. Hanya JPEG, PNG, GIF, WebP yang diizinkan.');
                    }

                    $safeName = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $user->name);
                    $fotoName = "uploads/" . $safeName . "@" . now()->format('d-m-Y_H-i-s') . ".png";
                    $directory = public_path('uploads');

                    if (!File::exists($directory)) {
                        File::makeDirectory($directory, 0755, true);
                    }

                    File::put(public_path($fotoName), $dFoto);
                }
            } catch (\Exception $e) {
                return back()->with('error', 'Terjadi kesalahan saat menyimpan foto absensi.');
            }
        }

        // 4. Simpan ke Database
        Absensi::create([
            'user_id' => $user->id,
            'nama'    => $user->name,
            'unit'    => $user->unit ?? 'SMP ABBS Surakarta',
            'lokasi'  => $request->lokasi,
            'alamat'  => $request->alamat ?? 'Alamat tidak tersedia',
            'waktu'   => now(),
            'foto'    => $fotoName,
        ]);

        $apiKey = config('fonnte.api_key');

        $dayMap = [
            'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu'
        ];

        $dayEn = now()->format('l');
        $dayId = $dayMap[$dayEn] ?? $dayEn;
        $tglNow = now()->format('d-m-Y');
        $jamNow = now()->format('H:i');

        $phone = preg_replace('/[^0-9]/', '', $user->phone_num ?? '');
        $phone = ltrim($phone, '0');

        if (empty($phone)) {
            Log::warning('Fonnte: phone_num kosong untuk user', ['user_id' => $user->id, 'name' => $user->name]);
        } else {
            $absen = Absensi::where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                        ->orWhere('nama', $user->name);
                })
                ->whereDate('waktu', now()->toDateString())
                ->first();

            $schedules = Schedule::where('day', $dayEn)
                ->where('teacher', 'LIKE', '%' . $user->name . '%')
                ->orderBy('period')
                ->get();

            $teachingList = $schedules->count() > 0 
                ? $schedules->map(fn($s) => "- Jam {$s->period}: {$s->subject} ({$s->class_name})")->implode("\n")
                : "(Tidak ada jadwal hari ini)";


            $jamAbsen = Carbon::parse($absen->waktu)->format('H:i');

            $message = "[NgajarYuk]\n\n"
                . "Halo *{$user->name}*, terima kasih sudah melakukan Presensi pada jam *{$jamAbsen}* ✅\n\n"
                . "Berikut jadwal mengajar Anda hari ini (*{$dayId}*):\n\n"
                . "{$teachingList}\n\n"
                . "📌 Jangan lupa untuk mengisi jurnal harian setelah kegiatan mengajar.\n\n"
                . "🔗 *Isi Jurnal:*\n"
                . "gurusmpabbs.alabidin.sch.id/journal\n\n"
                . "Tetap semangat mengajar! 💪\n"
                . "Tanggal: {$tglNow}\n\n"
                . "Waktu : *{$jamNow}*";

            $payload = [
                'target' => $phone,
                'message' => $message,
                'countryCode' => '62',
            ];

            $deviceId = config('fonnte.device_id');
            if (!empty($deviceId)) {
                $payload['device'] = $deviceId;
            }

            try {
                $response = Http::withHeaders([
                    'Authorization' => $apiKey,
                ])->timeout(10)->post(config('fonnte.api_url'), $payload);

                if ($response->successful()) {
                    $body = $response->json();
                    if (isset($body['status']) && $body['status'] === false) {
                        Log::warning('Fonnte API error', [
                            'user_id' => $user->id,
                            'phone' => $phone,
                            'response' => $body,
                        ]);
                    }
                } else {
                    Log::error('Fonnte HTTP error', [
                        'user_id' => $user->id,
                        'phone' => $phone,
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Fonnte exception', [
                    'user_id' => $user->id,
                    'phone' => $phone,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return redirect()->route('success')->with('success', 'Presensi berhasil disimpan!');
    }

    /**
     * Calculate distance between two coordinates using Haversine formula.
     * Returns distance in meters.
     */
    private function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2 +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
