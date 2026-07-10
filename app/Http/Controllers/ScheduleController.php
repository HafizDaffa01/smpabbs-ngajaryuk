<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ScheduleController extends Controller
{
    /**
     * Display schedule management page
     */
    public function index(Request $request): View
    {
        $classes = Schedule::getAllClasses();
        $selectedClass = $request->get('class', $classes->first());
        $selectedDay = $request->get('day', 'Monday');
        
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        
        $schedules = Schedule::getScheduleByClassAndDay($selectedClass, $selectedDay);
        
        return view('schedule.index', compact('classes', 'selectedClass', 'selectedDay', 'days', 'schedules'));
    }

    /**
     * Import schedules from Excel
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
            'confirm' => 'required|accepted',
        ]);

        try {
            $mapelMapping = $this->getMapelMapping();

            // Load all sheets as a collection of collections using Maatwebsite Excel Facade
            $sheets = Excel::toCollection(null, $request->file('file'));
            
            DB::beginTransaction();
            
            // Hapus jadwal lama sesuai info di UI
            Schedule::query()->delete();
            
            $totalImported = 0;
            
            // Process each sheet
            foreach ($sheets as $rows) {
                // Minimal check: baris 1 (Kelas) dan baris 2 (Hari)
                if ($rows->count() < 2) continue;

                $row1 = $rows[0]; // Baris 1: Nama Kelas
                $row2 = $rows[1]; // Baris 2: Nama Hari (Senin, Selasa, etc.)

                // Iterasi kolom di Baris 2 untuk mencari awal blok "Senin"
                foreach ($row2 as $colIndex => $cellValue) {
                    if (strtolower(trim((string)$cellValue)) === 'senin') {
                        // Ditemukan awal blok hari. Ambil nama kelas dari baris 1.
                        $className = null;
                        for ($c = $colIndex; $c >= 0; $c--) {
                            if (!empty($row1[$c])) {
                                $className = trim((string)$row1[$c]);
                                break;
                            }
                        }

                        if (!$className) continue;

                        // Proses 6 hari ke depan (Senin s/d Sabtu)
                        for ($dayOffset = 0; $dayOffset < 6; $dayOffset++) {
                            $currentCol = $colIndex + $dayOffset;
                            
                            $dayNameIndo = isset($row2[$currentCol]) ? trim((string)$row2[$currentCol]) : '';
                            $dayNameEng = $this->mapDay($dayNameIndo);
                            
                            if (!$dayNameEng || !in_array($dayNameEng, ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'])) continue;

                            // Iterasi Baris untuk Jam Pelajaran (dimulai dari Baris 3, index 2)
                            foreach ($rows as $rowIndex => $rowRows) {
                                if ($rowIndex < 2) continue; // Lewati header
                                if ($rowIndex > 50) break; // Safety limit
                                
                                $subject = isset($rowRows[$currentCol]) ? trim((string)$rowRows[$currentCol]) : null;
                                $period = $rowIndex - 1; // Baris 3 (index 2) -> Jam ke-1

                                if (!empty($subject) && $subject !== '-') {
                                    $subjectRaw = trim($subject);

                                    // Mapping & Splitting (Ikutin TeacherImport)
                                    $items = preg_split('/\s*(?:&|\+|dan)\s*/i', $subjectRaw);
                                    $mappedItems = [];
                                    
                                    foreach ($items as $item) {
                                        $item = trim((string)$item);
                                        if ($item !== '') {
                                            $key = strtolower($item);
                                            $mappedItems[] = $mapelMapping[$key] ?? $item;
                                        }
                                    }
                                    
                                    $subjectPart = implode(' & ', $mappedItems);

                                    Schedule::updateOrCreate(
                                        [
                                            'class_name' => $className,
                                            'day'        => $dayNameEng,
                                            'period'     => $period,
                                        ],
                                        [
                                            'subject_display' => $subjectPart,
                                            'teacher'         => null,
                                        ]
                                    );
                                    $totalImported++;
                                }
                            }
                        }
                    }
                }
            }
            
            DB::commit();
            
            if ($totalImported === 0) {
                return back()->with('error', 'Tidak ada data jadwal yang berhasil diimport. Pastikan format file sesuai.');
            }
            
            return back()->with('success', "Jadwal berhasil diimport! Total data: {$totalImported} jadwal.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan impor: ' . $e->getMessage());
        }
    }

    /**
     * Map Indonesian day name to English counterpart.
     */
    private function mapDay(?string $dayIndo): string
    {
        $map = [
            'SENIN'   => 'Monday',
            'SELASA'  => 'Tuesday',
            'RABU'    => 'Wednesday',
            'KAMIS'   => 'Thursday',
            'JUMAT'   => 'Friday',
            'JUM\'AT' => 'Friday',
            'SABTU'   => 'Saturday',
            'MINGGU'  => 'Sunday'
        ];
        
        $key = strtoupper(trim($dayIndo ?? ''));
        return $map[$key] ?? $key;
    }

    /**
     * Get subject mapping dictionary.
     */
    private function getMapelMapping(): array
    {
        return [
            'pai'         => 'IFE',
            'agama'       => 'IFE',
            'islam'       => 'IFE',
            'math'        => 'Mathematics',
            'mtk'         => 'Mathematics',
            'ipa'         => 'Science',
            'ips'         => 'Social',
            'pkn'         => 'Civics',
            'ppkn'        => 'Civics',
            'english'     => 'English',
            'inggris'     => 'English',
            'bahasa indo' => 'Indonesian',
            'indo'        => 'Indonesian',
            'indonesia'   => 'Indonesian',
            'sport'       => 'SPORT',
            'pjok'        => 'SPORT',
            'olga'        => 'SPORT',
            'olahraga'    => 'SPORT',
            'quran'       => 'Quran',
            "qur'an"      => 'Quran',
            'tka math'    => 'TKA Mathematics',
            'tka ind'     => 'TKA INDO',
            'tm'          => 'TKA Mathematics',
            'ti'          => 'TKA INDO',
            'ict'         => 'ICT',
            'computer'    => 'ICT',
            'science'     => 'Science',
            'leadership'  => 'Leadership',
        ];
    }
}
