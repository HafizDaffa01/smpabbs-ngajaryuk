<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Absensi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;
use ZipArchive;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function index(Request $request)
    {
        $months = [
            'jan_feb' => 'Januari 21 - Februari 20',
            'feb_mar' => 'Februari 21 - Maret 20',
            'mar_apr' => 'Maret 21 - April 20',
            'apr_mei' => 'April 21 - Mei 20',
            'mei_jun' => 'Mei 21 - Juni 20',
            'jun_jul' => 'Juni 21 - Juli 20',
            'jul_agu' => 'Juli 21 - Agustus 20',
            'agu_sep' => 'Agustus 21 - September 20',
            'sep_okt' => 'September 21 - Oktober 20',
            'okt_nov' => 'Oktober 21 - November 20',
            'nov_des' => 'November 21 - Desember 20',
            'des_jan' => 'Desember 21 - Januari 20',
        ];

        $firstDate = Absensi::min('waktu');
        $lastDate  = Absensi::max('waktu');

        $minYear = $firstDate ? Carbon::parse($firstDate)->year : now()->year;
        $maxYear = $lastDate ? Carbon::parse($lastDate)->year : now()->year;
        $years = range($minYear, $maxYear);

        $filterMonth = $request->month ?? '';
        $filterYear  = $request->year ?? now()->year;
        $dataType    = $request->data_type ?? 'waktu';
        $search      = $request->search ?? '';

        $days = [];
        $gridData = [];

        // ========== QUERY DASAR ==========
        $query = Absensi::query()
            ->when($search, function ($q) use ($search) {
                $q->whereHas('user', fn($uq) => $uq->where('name', 'like', "%$search%"));
            })
            ->when($filterMonth && $filterYear, function ($q) use ($filterMonth, $filterYear) {
                [$start, $end] = $this->getPeriodRange($filterMonth, $filterYear);
                $q->whereBetween('waktu', [$start, $end]);
            });

        $absensis = $query->with('user')->get();

        [$start, $end] = $this->getPeriodRange($filterMonth, $filterYear);

        // daftar hari dalam range
        $cursor = $start->copy();
        while ($cursor <= $end) {
            $days[] = $cursor->copy();
            $cursor->addDay();
        }

        foreach ($absensis as $a) {
            $userId = $a->user_id;
            $key = $userId ?? 'nouser_' . $a->nama;
            $name = $a->user?->name ?? $a->nama;
            $tgl  = Carbon::parse($a->waktu)->format('Y-m-d');
            $gridData[$key]['nama'] = $name;
            $gridData[$key]['unit'] = $a->unit ?? '-';

            if ($dataType === 'lokasi' || $dataType === 'gambar') {
                $gridData[$key]['data'][$tgl] = $a;
            } else {
                $jam = Carbon::parse($a->waktu)->format('H:i');
                $gridData[$key]['data'][$tgl] = $jam;
            }
        }

        usort($gridData, fn($a, $b) => strcasecmp($a['nama'], $b['nama']));
        $gridData = array_values($gridData);

        return view('backup.index', compact(
            'months',
            'years',
            'filterMonth',
            'filterYear',
            'dataType',
            'search',
            'absensis',
            'days',
            'gridData'
        ));
    }


    public function save(Request $request)
    {
        if ($request->has('data')) {
            $results = [];
            foreach ($request->data as $row) {
                $results[] = [
                    'nama'  => $row['nama'],
                    'date'  => $row['date'],
                    'value' => $this->saveAbsensiCell($row['nama'], $row['date'], $row['value'], $row['user_id'] ?? null)
                ];
            }
            return response()->json(['success' => true, 'results' => $results]);
        } else {
            $value = $this->saveAbsensiCell($request->nama, $request->date, $request->value, $request->user_id ?? null);
            return response()->json([
                'success' => true,
                'value'   => $value
            ]);
        }
    }

    private function saveAbsensiCell($nama, $date, $value, $userId = null)
    {
        // Default jika kosong
        if (trim((string)$value) === '' || $value === '-') {
            $value = '-';
        }

        // Simpan waktu (kalau "-", pakai 00:00:00 agar NOT NULL aman)
        if ($value === '-') {
            $waktu = Carbon::parse($date . ' 00:00:00');
            $jam = '-';
        } else {
            $waktu = Carbon::parse($date . ' ' . $value);
            $jam   = $waktu->format('H:i'); // format final
        }

        $absen = Absensi::where('waktu', '>=', Carbon::parse($date)->startOfDay())
            ->where('waktu', '<=', Carbon::parse($date)->endOfDay());

        if ($userId) {
            $absen = $absen->where('user_id', $userId);
        } else {
            $absen = $absen->where('nama', $nama);
        }

        $absen = $absen->first();

        if ($absen) {
            $absen->update(['waktu' => $waktu]);
        } else {
            $displayName = $nama;
            if ($userId) {
                $user = User::find($userId);
                if ($user) {
                    $displayName = $user->name;
                }
            }
            Absensi::create([
                'user_id' => $userId,
                'nama'    => $displayName,
                'waktu'   => $waktu,
                'unit'    => Auth::user()->unit ?? '-',
                'lokasi'  => '-',
                'foto'    => '-',
            ]);
        }

        return $jam;
    }

    public function exportWaktu(Request $request): StreamedResponse
    {
        $year = $request->year ?? date('Y');
        $monthKey = $request->month ?? 'jan_feb';
        $search = $request->search;

        $ranges = [
            'jan_feb' => ['start' => [1, 21],  'end' => [2, 20]],
            'feb_mar' => ['start' => [2, 21],  'end' => [3, 20]],
            'mar_apr' => ['start' => [3, 21],  'end' => [4, 20]],
            'apr_mei' => ['start' => [4, 21],  'end' => [5, 20]],
            'mei_jun' => ['start' => [5, 21],  'end' => [6, 20]],
            'jun_jul' => ['start' => [6, 21],  'end' => [7, 20]],
            'jul_agu' => ['start' => [7, 21],  'end' => [8, 20]],
            'agu_sep' => ['start' => [8, 21],  'end' => [9, 20]],
            'sep_okt' => ['start' => [9, 21],  'end' => [10, 20]],
            'okt_nov' => ['start' => [10, 21], 'end' => [11, 20]],
            'nov_des' => ['start' => [11, 21], 'end' => [12, 20]],
            'des_jan' => ['start' => [12, 21], 'end' => [1, 20]],
        ];

        if (!isset($ranges[$monthKey])) {
            abort(400, 'Periode tidak valid');
        }

        $range = $ranges[$monthKey];

        $start = Carbon::create($year, $range['start'][0], $range['start'][1]);
        $endYear = $range['end'][0] == 1 ? $year + 1 : $year;
        $end   = Carbon::create($endYear, $range['end'][0], $range['end'][1]);

        $days = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $days[] = $cursor->copy();
            $cursor->addDay();
        }

        $absensis = Absensi::query()
            ->when($search, function ($q) use ($search) {
                $q->whereHas('user', fn($uq) => $uq->where('name', 'like', "%$search%"));
            })
            ->whereBetween('waktu', [$start, $end->endOfDay()])
            ->with('user')
            ->orderBy('user_id')
            ->get();

        $grid = [];
        foreach ($absensis as $a) {
            $userId = $a->user_id;
            $key = $userId ?? 'nouser_' . $a->nama;
            $name = $a->user?->name ?? $a->nama;
            $tgl = Carbon::parse($a->waktu)->format('Y-m-d');
            if (!isset($grid[$key])) {
                $grid[$key]['nama'] = $name;
            }
            $grid[$key]['data'][$tgl] = Carbon::parse($a->waktu)->format('H:i');
        }

        uasort($grid, fn($a, $b) => strcasecmp($a['nama'], $b['nama']));
        $uuid = substr((string) Str::uuid(), 0, 7);
        $filename = "Backup_Absensi@{$monthKey}_{$year}#{$uuid}.csv";

        return response()->streamDownload(function () use ($grid, $days, $start, $end) {
            $output = fopen('php://output', 'w');

            // Header Range Tanggal
            fputcsv($output, [$start->translatedFormat('d F') . ' - ' . $end->translatedFormat('d F Y')]);

            // Header Tabel
            $header = ['No', 'Nama'];
            foreach ($days as $d) {
                $header[] = $d->format('j');
            }
            $header[] = '21';
            $header[] = 'TOT';
            fputcsv($output, $header);

            // Data Tabel
            $no = 1;
            foreach ($grid as $row) {
                $line = [$no++, $row['nama']];
                $tot = 0;

                foreach ($days as $d) {
                    $tgl = $d->format('Y-m-d');
                    $val = $row['data'][$tgl] ?? '-';
                    $line[] = $val;

                    if ($val !== '-' && $val <= '06:50') {
                        $tot++;
                    }
                }

                $line[] = '';
                $line[] = $tot;
                fputcsv($output, $line);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=utf-8',
        ]);
    }
    
    public function exportLokasi(Request $request)
    {
        $search = $request->search;
        $year = $request->year ?? date('Y');

        $absensis = Absensi::query()
            ->when($search, function ($q) use ($search) {
                $q->whereHas('user', fn($uq) => $uq->where('name', 'like', "%$search%"));
            })
            ->whereYear('waktu', $year)
            ->with('user')
            ->orderBy('user_id')
            ->get(['id', 'user_id', 'nama', 'lokasi', 'alamat', 'waktu']);

        if ($absensis->isEmpty()) {
            return back()->with('error', 'Tidak ada data absensi untuk diekspor.');
        }

        $uuid = substr((string) Str::uuid(), 0, 7);
        $filename = "Backup_Lokasi@{$year}#{$uuid}.csv";

        return response()->streamDownload(function () use ($absensis) {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['No', 'Nama', 'Lokasi (lat,lon)', 'Alamat', 'Waktu']);

            $no = 1;
            foreach ($absensis as $a) {
                fputcsv($output, [
                    $no++,
                    $a->user?->name ?? $a->nama,
                    $a->lokasi,
                    $a->alamat,
                    Carbon::parse($a->waktu)->format('d-m-Y H:i')
                ]);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=utf-8',
        ]);
    }

    public function exportGambar(Request $request)
    {
        $data = Absensi::whereNotNull('foto')->get();

        if ($data->isEmpty()) {
            return back()->with('error', 'Tidak ada data gambar untuk diekspor.');
        }

        $filename = "Backup_Gambar@" . date('d-m-Y') . "#" . substr((string) Str::uuid(), 0, 5) . ".zip";
        $tempDir = storage_path("app/temp");
        $zipPath = $tempDir . '/' . $filename;

        if (!File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                foreach ($data as $a) {
                    $uuid = substr((string) Str::uuid(), 0, 7);
                    $ext  = pathinfo($a->foto, PATHINFO_EXTENSION) ?: 'jpg';
                    $displayName = $a->user?->name ?? $a->nama;
                    $name = "{$displayName}@" . date('d-m-Y') . "#{$uuid}.{$ext}";

                $filePath = public_path($a->foto);
                if (file_exists($filePath)) {
                    $zip->addFile($filePath, $name);
                }
            }
            $zip->close();
        } else {
            return back()->with('error', 'Gagal membuat file archive zip.');
        }

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    private function getPeriodRange($month, $year)
    {
        $monthMap = [
            'jan_feb' => [1, 2],
            'feb_mar' => [2, 3],
            'mar_apr' => [3, 4],
            'apr_mei' => [4, 5],
            'mei_jun' => [5, 6],
            'jun_jul' => [6, 7],
            'jul_agu' => [7, 8],
            'agu_sep' => [8, 9],
            'sep_okt' => [9, 10],
            'okt_nov' => [10, 11],
            'nov_des' => [11, 12],
            'des_jan' => [12, 1],
        ];

        $months = $monthMap[$month] ?? [1, 2];
        $startMonth = $months[0];
        $endMonth   = $months[1];

        $startYear = $year;
        $endYear   = $year;

        if ($startMonth == 12 && $endMonth == 1) {
            $endYear = $year + 1;
        }

        $start = Carbon::createFromDate($startYear, $startMonth, 21)->startOfDay();
        $end   = Carbon::createFromDate($endYear, $endMonth, 20)->endOfDay();

        return [$start, $end];
    }
}
