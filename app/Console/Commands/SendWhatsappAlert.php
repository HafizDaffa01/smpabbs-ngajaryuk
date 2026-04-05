<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Absensi;
use App\Models\Schedule;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class SendWhatsappAlert extends Command
{
    protected $signature = 'send:whatsapp-alert';
    protected $description = 'Kirim WhatsApp otomatis berdasarkan absensi';

    public function handle()
    {
        $users = User::whereNotNull('phone_num')->get();
        $apiKey = env('FONNTE_API_KEY');

        if (!$apiKey) {
            $this->error('[!] API KEY belum diset');
            return;
        }

        $dayMap = [
            'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu'
        ];

        $dayEn = now()->format('l');
        $dayId = $dayMap[$dayEn] ?? $dayEn;
        $tglNow = now()->format('d-m-Y');
        $jamNow = now()->format('H:i');

        $total = $users->count();

        $this->info("[i] Mulai kirim ke {$total} user...\n");

        foreach ($users as $index => $user) {

            $phone = preg_replace('/[^0-9]/', '', $user->phone_num);
            $phone = ltrim($phone, '0');

            if (!$phone) continue;

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



            if ($absen) {

                continue;

            } else {

                if ($jamNow >= '08:00') {

                    $message = "[REMINDER ABSEN]\n\n"
                        . "Halo *{$user->name}*, kami mendeteksi bahwa Anda *belum melakukan absensi hari ini* ❗\n\n"
                        . "Mohon segera melakukan absensi dan mengisi jurnal harian agar data kehadiran tercatat dengan baik.\n\n"
                        . "⏰ *Perhatian:* Absensi yang terlambat dapat mempengaruhi pencatatan kehadiran.\n\n"
                        . "🔗 *Absen Sekarang:*\n"
                        . "gurusmpabbs.alabidin.sch.id/absensi\n\n"
                        . "🔗 *Isi Jurnal:*\n"
                        . "gurusmpabbs.alabidin.sch.id/journal\n\n"
                        . "Terima kasih atas perhatian dan kerjasamanya 🙏\n"
                        . "Tanggal: {$tglNow}\n"
                        . "Waktu: *{$jamNow}*";

                } else {
                    continue;
                }
            }

            $this->info("[" . ($index+1) . "/{$total}] Kirim ke {$phone}");

            try {
                $response = Http::withHeaders([
                    'Authorization' => $apiKey,
                ])->post('https://api.fonnte.com/send', [
                    'target' => $phone,
                    'message' => $message,
                    'countryCode' => '62',
                ]);

                if ($response->successful()) {
                    $this->info("[i] Berhasil");
                } else {
                    $this->error("[!] Gagal");
                }

            } catch (\Exception $e) {
                $this->error("[!] Error: " . $e->getMessage());
            }

            usleep(500000);
        }

        $this->info("\n[i] Selesai!");
    }
}
