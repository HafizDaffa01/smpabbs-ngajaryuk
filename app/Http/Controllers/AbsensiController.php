<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Absensi;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use App\Models\Teacher;

class AbsensiController extends Controller
{
    /**
     * Display the absensi form page.
     */
    public function index()
    {
        // Cegah admin mengakses halaman absensi
        if (Auth::user()->is_admin) {
            abort(403, 'Unauthorized. Hanya pengguna non-admin yang diizinkan.');
        }

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
        ]);

        $user = Auth::user();

        // 2. Cegah Absensi Ganda pada Hari yang Sama
        $sudahAbsen = Absensi::where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->orWhere('nama', $user->name);
            })
            ->whereDate('waktu', now()->toDateString())
            ->first();

        if ($sudahAbsen) {
            $jamAbsen = Carbon::parse($sudahAbsen->waktu)->format('H:i');
            return redirect()->route('error')->with('error', "Anda sudah absen pada jam {$jamAbsen}");
        }

        // 3. Proses File Base64 Menggunakan Facade Laravel
        $fotoName = null;
        if ($request->filled('foto')) {
            try {
                // Menghapus base64 header
                $fotoData = preg_replace('/^data:image\/\w+;base64,/', '', $request->foto);
                $dFoto = base64_decode($fotoData);
                
                if ($dFoto !== false) {
                    $fotoName = "uploads/" . filter_var($user->name, FILTER_SANITIZE_STRING) . "@" . now()->format('d-m-Y_H-i-s') . ".png";
                    $directory = public_path('uploads');

                    // Pastikan folder uploads tersedia
                    if (!File::exists($directory)) {
                        File::makeDirectory($directory, 0755, true);
                    }

                    // Gunakan FileFacade Laravel dibandingkan native file_put_contents
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

        $users = User::whereNotNull('phone_num')->get();
        $apiKey = env('FONNTE_API_KEY');

        $dayMap = [
            'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu'
        ];

        $dayEn = now()->format('l');
        $dayId = $dayMap[$dayEn] ?? $dayEn;
        $tglNow = now()->format('d-m-Y');
        $jamNow = now()->format('H:i');

        $phone = preg_replace('/[^0-9]/', '', $user->phone_num);
        $phone = ltrim($phone, '0');

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
            . "Halo *{$user->name}*, terima kasih sudah melakukan absensi pada jam *{$jamAbsen}* ✅\n\n"
            . "Berikut jadwal mengajar Anda hari ini (*{$dayId}*):\n\n"
            . "{$teachingList}\n\n"
            . "📌 Jangan lupa untuk mengisi jurnal harian setelah kegiatan mengajar.\n\n"
            . "🔗 *Isi Jurnal:*\n"
            . "gurusmpabbs.alabidin.sch.id/journal\n\n"
            . "Tetap semangat mengajar! 💪\n"
            . "Tanggal: {$tglNow}\n\n"
            . "Waktu : *{$jamNow}*";

        Http::withHeaders([
            'Authorization' => $apiKey,
        ])->post('https://api.fonnte.com/send', [
            'target' => $phone,
            'message' => $message,
            'countryCode' => '62',
        ]);

        return redirect()->route('success')->with('success', 'Absensi berhasil disimpan!');
    }
}
