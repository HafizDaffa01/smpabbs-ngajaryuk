<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Schedule;
use Illuminate\Support\Facades\Http;

class SendWhatsappAlert extends Command
{
    protected $signature = 'send:whatsapp-alert';
    protected $description = 'Kirim WhatsApp ke semua user (jadwal harian)';

    public function handle()
    {
        $users = User::whereNotNull('phone_num')->get();
        $apiKey = env('FONNTE_API_KEY');

        if (!$apiKey) {
            $this->error('[!] API KEY belum diset di .env');
            return;
        }

        if ($users->isEmpty()) {
            $this->error('[!] Tidak ada user dengan nomor HP');
            return;
        }

        $dayMap = [
            'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu'
        ];

        $dayEn = now()->format('l');
        $dayId = $dayMap[$dayEn] ?? $dayEn;
        $tglNow = now()->format('d-m-Y');

        $total = $users->count();

        $this->info("[i] Mulai kirim WhatsApp ke {$total} user...\n");

        foreach ($users as $index => $user) {

            $phone = preg_replace('/[^0-9]/', '', $user->phone_num);
            $phone = ltrim($phone, '0');

            if (!$phone) {
                $this->error("[!] Nomor kosong / invalid untuk {$user->name}");
                continue;
            }

            $schedules = Schedule::where('day', $dayEn)
                ->where('teacher', 'LIKE', '%' . $user->name . '%')
                ->orderBy('period')
                ->get();

            $teachingList = $schedules->count() > 0 
                ? $schedules->map(function($s) {
                    return "- Jam *{$s->subject}* - Kelas *{$s->class_name}*";
                })->implode("\n")
                : "(Belum ada jadwal mengajar hari ini)";

            $message = "[NgajarYuk]\n\nHalo *{$user->name}*, berikut jadwal mengajar Anda hari *{$dayId}*:\n\n"
                . "{$teachingList}\n\n"
                . "Jangan lupa mengisi jurnal harian ya!\n\n"
                . "Pesan ini dikirim pada: {$tglNow}";

            $this->info("[" . ($index+1) . "/{$total}] Kirim ke: {$phone}");

            try {
                $response = Http::withHeaders([
                    'Authorization' => $apiKey,
                ])->post('https://api.fonnte.com/send', [
                    'target' => $phone,
                    'message' => $message,
                    'countryCode' => '62',
                ]);

                if ($response->successful()) {
                    $this->info("[i] Berhasil ke {$phone}");
                } else {
                    $this->error("[!] Gagal ke {$phone}");
                    $this->error($response->body());
                }

            } catch (\Exception $e) {
                $this->error("[!] Error ke {$phone}");
                $this->error($e->getMessage());
            }

            usleep(500000); // 0.5 detik
        }

        $this->info("\n[i] Selesai kirim semua WhatsApp!");
    }
}
