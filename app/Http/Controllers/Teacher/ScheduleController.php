<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;

use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use App\Models\Teacher;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ScheduleController extends Controller
{
    /**
     * Mapping nama hari (Indonesia/Inggris) ke string standar Inggris
     */
    private const DAY_MAP = [
        'SENIN'     => 'Monday',
        'SELASA'    => 'Tuesday',
        'RABU'      => 'Wednesday',
        'KAMIS'     => 'Thursday',
        'JUMAT'     => 'Friday',
        "JUM'AT"    => 'Friday',
        'SABTU'     => 'Saturday',
        'MONDAY'    => 'Monday',
        'TUESDAY'   => 'Tuesday',
        'WEDNESDAY' => 'Wednesday',
        'THURSDAY'  => 'Thursday',
        'FRIDAY'    => 'Friday',
        'SATURDAY'  => 'Saturday',
    ];

    /**
     * Sheet names untuk mapel inti (selalu diakhiri titik)
     */
    private const SUBJECT_SHEETS = [
        'Sprt.', 'Soc.', 'Sc.', 'Quran.', 'Math.', 'IFE.', 'ICT.', 'Eng.', 'Cv.', 'BI.',
    ];

    /**
     * Sheet names untuk Leadership (perhatikan typo "LEASDERSHIP" untuk kelas 9)
     */
    private const LEADERSHIP_SHEETS = [
        'LEADERSHIP 7.', 'LEADERSHIP 8.', 'LEASDERSHIP 9.',
    ];

    private const LEADERSHIP_CODE_OF_SHEET = [
        'LEADERSHIP 7.' => 'L7',
        'LEADERSHIP 8.' => 'L8',
        'LEASDERSHIP 9.' => 'L9',
    ];

    private const LEADERSHIP_LABEL = [
        'L7' => 'Leadership 7', 'L8' => 'Leadership 8', 'L9' => 'Leadership 9',
    ];

    private const LEADERSHIP_CLASS_MARKER = '__LEADERSHIP__';

    /**
     * Sheet names untuk mapel tanpa guru spesifik
     */
    private const WITHOUT_TEACHER_SHEETS = [
        'HOMEROOM TEACHER.', 'SCOUT.', 'SENI BUDAYA KESENIAN.', 'SELF DEVELOPMENT.',
    ];

    /**
     * Mapping short kode mapel aSc → nama tampilan lengkap
     */
    private const SUBJECT_DISPLAY_MAP = [
        'Sprt' => 'SPORT', 'Soc' => 'Social', 'Sc' => 'Science', 'Quran' => 'Quran',
        'Math' => 'Mathematics', 'IFE' => 'IFE', 'ICT' => 'ICT', 'Eng' => 'English',
        'Cv' => 'Civics', 'BI' => 'Indonesian',
    ];

    /**
     * Valid jam pelajaran per hari (Gotcha #1: Jumat tidak ada jam 6)
     */
    private const VALID_LESSONS_BY_DAY = [
        'Monday'    => [1, 2, 3, 4, 5, 6, 7, 8, 9],
        'Tuesday'   => [1, 2, 3, 4, 5, 6, 7, 8, 9],
        'Wednesday' => [1, 2, 3, 4, 5, 6, 7, 8, 9],
        'Thursday'  => [1, 2, 3, 4, 5, 6, 7, 8, 9],
        'Friday'    => [1, 2, 3, 4, 5, 7, 8, 9],
        'Saturday'  => [1, 2, 3, 4, 5, 6],
    ];

    /**
     * Remap Lesson# Jumat (Gotcha #1: raw 6→7, 7→8, 8→9)
     */
    private const FRIDAY_RAW_TO_DISPLAY = [6 => 7, 7 => 8, 8 => 9];

    /**
     * Jam bel: hari biasa (Senin-Kamis)
     */
    private const WEEKDAY_BELLS = [
        1 => ['07:30:00', '08:10:00'],
        2 => ['08:10:00', '08:50:00'],
        4 => ['09:50:00', '10:30:00'],
        5 => ['10:30:00', '11:10:00'],
        6 => ['11:10:00', '11:50:00'],
        7 => ['13:00:00', '13:40:00'],
        8 => ['13:40:00', '14:20:00'],
        9 => ['14:20:00', '15:00:00'],
    ];
    private const WEEKDAY_BELL3 = ['boys' => ['09:10:00', '09:50:00'], 'girls' => ['08:50:00', '09:30:00']];

    private const FRIDAY_BELLS = [
        1 => ['07:30:00', '08:10:00'],
        2 => ['08:10:00', '08:50:00'],
        4 => ['09:50:00', '10:30:00'],
        5 => ['10:30:00', '11:10:00'],
        7 => ['13:00:00', '13:40:00'],
        8 => ['13:40:00', '14:20:00'],
        9 => ['14:20:00', '15:00:00'],
    ];
    private const FRIDAY_BELL3 = ['boys' => ['09:10:00', '09:50:00'], 'girls' => ['08:50:00', '09:30:00']];

    private const SATURDAY_BELLS = [
        1 => ['07:15:00', '07:45:00'],
        2 => ['07:45:00', '08:15:00'],
        4 => ['09:00:00', '09:30:00'],
        5 => ['09:30:00', '10:00:00'],
        6 => ['10:00:00', '10:30:00'],
    ];
    private const SATURDAY_BELL3 = ['boys' => ['08:15:00', '08:45:00'], 'girls' => ['08:30:00', '09:00:00']];

    // ─────────────────────────────────────────────────────────

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
     * Import jadwal dari satu file v9.4.xlsx (aSc Timetables).
     * File ini berisi data guru (Teachers sheet), mapping guru-kelas-mapel (Lessons sheet),
     * dan jadwal per mapel (sheet mapel inti, Leadership, tanpa guru).
     */
    public function import(Request $request): RedirectResponse
    {
        \Log::info('Import v9.4: PHP upload limits', [
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
        ]);
        \Log::info('Import v9.4: request started', [
            'has_file' => $request->hasFile('file_v94'),
            'confirm' => $request->input('confirm'),
            'all_input' => $request->all(),
        ]);

        $request->validate([
            'file_v94' => 'required|file|mimes:xlsx,xls|max:20480',
            'confirm'  => 'required|accepted',
        ]);

        try {
            $v94 = IOFactory::load($request->file('file_v94')->getRealPath());

            \Log::info('Import v9.4: sheets', $v94->getSheetNames());

            // ── 1. Baca data referensi dari v9.4 ──────────────
            $nicknameMap = $this->readTeachersSheet($v94);
            $lessonsResult = $this->readLessonsSheet($v94, $nicknameMap);
            $teacherMap = $lessonsResult['teacherMap'];
            $leadershipParticipants = $lessonsResult['leadershipParticipants'];
            $allClasses = $this->readClassesSheet($v94);

            \Log::info('Import v9.4: teachers', ['count' => count($nicknameMap)]);
            \Log::info('Import v9.4: teacherMap entries', ['count' => count($teacherMap)]);
            \Log::info('Import v9.4: classes', ['count' => count($allClasses)]);

            // ── 2. Import guru ke tabel users ──────────────────
            DB::beginTransaction();

            $totalTeachers = $this->importTeachersFromV94($v94, $teacherMap);
            \Log::info('Import v9.4: teachers saved to DB', ['count' => $totalTeachers]);

            // ── 3. Hapus jadwal lama ───────────────────────────
            Schedule::query()->delete();

            $totalImported = 0;

            // ── 4. Cek apakah file menggunakan format "Available teachers" ──
            $hasAvailableTeachers = $v94->getSheetByName('Available teachers') !== null
                || $v94->getSheetByName('Available teachers 2') !== null;

            // Build nickname → full name mapping for Available teachers 2 fallback
            $nickToFull = [];
            $teacherSheet = $v94->getSheetByName('Teachers');
            if ($teacherSheet) {
                foreach ($teacherSheet->getRowIterator(2, 500) as $row) {
                    $r = $row->getRowIndex();
                    $name = trim((string) $teacherSheet->getCell([2, $r])->getValue());
                    $nick = trim((string) $teacherSheet->getCell([3, $r])->getValue());
                    if ($name !== '' && $nick !== '') {
                        $nickToFull[$nick] = $name;
                    }
                }
            }

            if ($hasAvailableTeachers) {
                // Format teacher-centric: Available teachers sheets
                $availSheet = $v94->getSheetByName('Available teachers');
                $availSheet2 = $v94->getSheetByName('Available teachers 2');
                \Log::info('Import v9.4: Available teachers sheets', [
                    'has_available' => $availSheet !== null,
                    'has_available2' => $availSheet2 !== null,
                ]);
                
                $totalImported += $this->processAvailableTeachersFormat(
                    $v94,
                    $allClasses,
                    $lessonsResult['teacherToClassesSubjects'],
                    $lessonsResult['teacherNickToClassesSubjects'],
                    $nickToFull
                );
                \Log::info('Import v9.4: Available teachers format → ' . $totalImported . ' records');
            } else {
                // Format per-subject sheet (format lama)
                foreach (self::SUBJECT_SHEETS as $sheetname) {
                    $ws = $v94->getSheetByName($sheetname);
                    if (!$ws) continue;
                    $imported = $this->processSubjectSheet($ws, $teacherMap, $allClasses);
                    \Log::info('Import v9.4: subject sheet ' . $sheetname . ' → ' . $imported . ' records');
                    $totalImported += $imported;
                }
            }

            // ── 5. Parse 3 sheet Leadership ────────────────────
            foreach (self::LEADERSHIP_SHEETS as $sheetname) {
                $ws = $v94->getSheetByName($sheetname);
                if (!$ws) continue;
                $code = self::LEADERSHIP_CODE_OF_SHEET[$sheetname];
                $participants = $leadershipParticipants[$code] ?? [];
                $imported = $this->processLeadershipSheet($ws, $participants, $code);
                \Log::info('Import v9.4: leadership sheet ' . $sheetname . ' → ' . $imported . ' records');
                $totalImported += $imported;
            }

            // ── 6. Parse 4 sheet tanpa guru ────────────────────
            foreach (self::WITHOUT_TEACHER_SHEETS as $sheetname) {
                $ws = $v94->getSheetByName($sheetname);
                if (!$ws) continue;
                $imported = $this->processWithoutTeacherSheet($ws, $allClasses);
                \Log::info('Import v9.4: without-teacher sheet ' . $sheetname . ' → ' . $imported . ' records');
                $totalImported += $imported;
            }

            DB::commit();

            \Log::info('Import v9.4: final result', [
                'teachers' => $totalTeachers,
                'schedules' => $totalImported,
            ]);

            if ($totalImported === 0) {
                return back()->with('error', 'Tidak ada data jadwal yang berhasil diimport. Pastikan file v9.4.xlsx sesuai format yang diharapkan.');
            }

            return back()->with('success', "Berhasil! {$totalTeachers} guru & {$totalImported} slot jadwal diimport dari v9.4.xlsx.");

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Import v9.4 error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Terjadi kesalahan impor: ' . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════════════════
    // PRIVATE HELPERS — Baca data referensi dari v9.4
    // ═══════════════════════════════════════════════════════════

    /**
     * Baca sheet "Classes" dari v9.4.xlsx.
     * Kolom A berisi daftar kode kelas, mulai baris 2.
     * Kelas dinormalisasi (suffix ICT/TCP/IFE dihapus) agar konsisten dengan pemrosesan sheet mapel.
     */
    private function readClassesSheet($spreadsheet): array
    {
        $sheet = $spreadsheet->getSheetByName('Classes');
        if (!$sheet) return [];

        $classes = [];
        foreach ($sheet->getRowIterator(2, 200) as $row) {
            $r = $row->getRowIndex();
            $cls = trim((string) $sheet->getCell([1, $r])->getValue());
            if ($cls === '') break;
            // Normalisasi kelas agar konsisten dengan pemrosesan sheet mapel
            $classes[] = $this->normalizeClassName($cls);
        }
        return array_unique($classes);
    }

    /**
     * Baca sheet "Teachers" dari v9.4.xlsx.
     * Kolom B = nama lengkap, kolom C = nickname, kolom F = Contract jam.
     * Return: ['nama_lengkap' => 'nickname', ...]
     */
    private function readTeachersSheet($spreadsheet): array
    {
        $sheet = $spreadsheet->getSheetByName('Teachers');
        if (!$sheet) return [];

        $map = [];
        foreach ($sheet->getRowIterator(2, 500) as $row) {
            $r = $row->getRowIndex();
            $name = trim((string) $sheet->getCell([2, $r])->getValue());
            $nick = trim((string) $sheet->getCell([3, $r])->getValue());
            if ($name !== '' && $nick !== '') {
                $map[$name] = $nick;
            }
        }
        return $map;
    }

    /**
     * Baca sheet "Lessons" dari v9.4.xlsx.
     * Kolom 1 = guru, kolom 2 = kelas (comma-sep), kolom 4 = kode mapel.
     * Return: [
     *   'teacherMap' => ['kelas|subjek_normalized' => ['Guru1', 'Guru2'], ...],
     *   'leadershipParticipants' => ['L7' => [nickname, ...], 'L8' => [...], 'L9' => [...]],
     *   'teacherToClassesSubjects' => ['Nama Lengkap Guru' => [['kelas', 'subjek'], ...], ...],
     *   'teacherNickToClassesSubjects' => ['nickname' => [['kelas', 'subjek'], ...], ...],
     * ]
     */
    private function readLessonsSheet($spreadsheet, array $nicknameMap): array
    {
        $sheet = $spreadsheet->getSheetByName('Lessons');
        if (!$sheet) return ['teacherMap' => [], 'leadershipParticipants' => [], 'teacherToClassesSubjects' => [], 'teacherNickToClassesSubjects' => []];

        $teacherOf = [];
        $leadershipParticipants = [];
        $teacherToClassesSubjects = [];
        $teacherNickToClassesSubjects = [];

        foreach ($sheet->getRowIterator(2, 1000) as $row) {
            $r = $row->getRowIndex();
            $teacherRaw = trim((string) $sheet->getCell([1, $r])->getValue());
            $classField = trim((string) $sheet->getCell([2, $r])->getValue());
            $subjectRaw = trim((string) $sheet->getCell([4, $r])->getValue());

            if ($subjectRaw === '' || $classField === '') continue;
            if ($teacherRaw === 'Without teacher' || $teacherRaw === '') continue;

            // Resolve nama lengkap → nickname
            $teachers = [];
            foreach (array_map('trim', explode(',', $teacherRaw)) as $name) {
                $teachers[] = $nicknameMap[$name] ?? $name;
            }
            $teachers = array_filter($teachers);

            // Cek apakah ini Leadership
            $canonicalSubject = \App\Helpers\SubjectHelper::normalize($subjectRaw);

            // Deteksi Leadership dari kode sheet atau nama subject
            $leadershipCode = null;
            foreach (self::LEADERSHIP_LABEL as $code => $label) {
                if ($canonicalSubject === 'Leadership' || $subjectRaw === $code || $subjectRaw === $label) {
                    $leadershipCode = $code;
                    break;
                }
            }

            if ($leadershipCode) {
                $leadershipParticipants[$leadershipCode] = array_merge(
                    $leadershipParticipants[$leadershipCode] ?? [],
                    $teachers
                );
                $leadershipParticipants[$leadershipCode] = array_unique($leadershipParticipants[$leadershipCode]);
                continue;
            }

            if (!$canonicalSubject) continue;

            // Normalisasi nama kelas dari v9.4 → nama kelas di file jadwal
            $classes = array_map('trim', explode(',', $classField));
            foreach ($classes as $cls) {
                $normalizedCls = $this->normalizeClassName($cls);
                $key = $normalizedCls . '|' . $canonicalSubject;
                if (!isset($teacherOf[$key])) $teacherOf[$key] = [];
                $teacherOf[$key] = array_unique(array_merge($teacherOf[$key], $teachers));

                // Build reverse maps: teacher → [(class, subject), ...]
                foreach ($teachers as $teacherNick) {
                    $teacherNickToClassesSubjects[$teacherNick][] = [$normalizedCls, $canonicalSubject];
                }
            }
        }

        // Build full-name reverse map by resolving nicknames back to full names
        // We need the Teachers sheet for this mapping
        $teacherSheet = $spreadsheet->getSheetByName('Teachers');
        if ($teacherSheet) {
            $nickToFull = [];
            foreach ($teacherSheet->getRowIterator(2, 500) as $row) {
                $r = $row->getRowIndex();
                $name = trim((string) $teacherSheet->getCell([2, $r])->getValue());
                $nick = trim((string) $teacherSheet->getCell([3, $r])->getValue());
                if ($name !== '' && $nick !== '') {
                    $nickToFull[$nick] = $name;
                }
            }
            // Map full names from Lessons to their nicknames, then build full-name reverse map
            // Actually, we need: for each teacher full name in Lessons, find their nickname,
            // then use the nickname to look up in teacherNickToClassesSubjects.
            // But we already have teacherNickToClassesSubjects keyed by nickname.
            // We need teacherToClassesSubjects keyed by full name.
            // So we need to map each nickname in teacherNickToClassesSubjects back to its full name.
            foreach ($teacherNickToClassesSubjects as $nick => $entries) {
                $fullName = $nickToFull[$nick] ?? $nick;
                if (!isset($teacherToClassesSubjects[$fullName])) {
                    $teacherToClassesSubjects[$fullName] = [];
                }
                $teacherToClassesSubjects[$fullName] = array_merge($teacherToClassesSubjects[$fullName], $entries);
            }
        }

        return ['teacherMap' => $teacherOf, 'leadershipParticipants' => $leadershipParticipants, 'teacherToClassesSubjects' => $teacherToClassesSubjects, 'teacherNickToClassesSubjects' => $teacherNickToClassesSubjects];
    }

    /**
     * Import semua guru dari Teachers sheet v9.4 ke tabel users.
     */
    private function importTeachersFromV94($spreadsheet, array $teacherMap): int
    {
        $sheet = $spreadsheet->getSheetByName('Teachers');
        if (!$sheet) return 0;

        // Balik teacherMap: nickname → list of 'kelas|mapel'
        $nicknameToMapel = [];
        foreach ($teacherMap as $key => $teachers) {
            [$kelas, $mapel] = explode('|', $key, 2);
            foreach ($teachers as $nick) {
                if (!isset($nicknameToMapel[$nick])) $nicknameToMapel[$nick] = [];
                $nicknameToMapel[$nick][] = ['mapel' => $mapel, 'kelas' => $kelas];
            }
        }

        $count = 0;
        $importedEmails = [];

        foreach ($sheet->getRowIterator(2, 200) as $row) {
            $r = $row->getRowIndex();
            $name = trim((string) $sheet->getCell([2, $r])->getValue());
            $short = trim((string) $sheet->getCell([3, $r])->getValue());

            if ($name === '') break;

            $emailLocal = strtolower(preg_replace('/[^a-zA-Z0-9.]/', '.', $name));
            $emailLocal = preg_replace('/\.{2,}/', '.', trim($emailLocal, '.'));
            $email = $emailLocal . '@abbs.sch.id';
            $importedEmails[] = $email;

            $mapelJson = $nicknameToMapel[$short] ?? [];

            $teacher = Teacher::firstOrNew(['email' => $email]);
            $teacher->name = $name;
            $teacher->is_admin = 0;
            $teacher->mapel = json_encode($mapelJson);
            if (!$teacher->exists) {
                $teacher->password = Hash::make('abbs2024');
            }
            $teacher->save();
            $count++;
        }

        // Hapus guru lama yang tidak ada di v9.4
        Teacher::where('is_admin', 0)
            ->whereNotIn('email', $importedEmails)
            ->delete();

        return $count;
    }

    // ═══════════════════════════════════════════════════════════
    // PRIVATE HELPERS — Parse sheet jadwal dari v9.4
    // ═══════════════════════════════════════════════════════════

    /**
     * Remap Lesson# untuk hari Jumat (Gotcha #1).
     */
    private function remapFridayLesson(string $day, int $rawLesson): int
    {
        if ($day === 'Friday') {
            return self::FRIDAY_RAW_TO_DISPLAY[$rawLesson] ?? $rawLesson;
        }
        return $rawLesson;
    }

    /**
     * Proses satu sheet mapel inti (Sprt., Soc., Sc., dst.) dari v9.4.
     * Format: Col B = hari, Col C = Lesson# (raw), Col D+ = kelas (comma-sep).
     */
    private function processSubjectSheet($sheet, array $teacherMap, array $allClasses): int
    {
        $highestRow = $sheet->getHighestRow();
        $subjectRaw = $sheet->getTitle();
        $subjectKey = rtrim($subjectRaw, '.');
        $canonicalSubject = \App\Helpers\SubjectHelper::normalize($subjectRaw);
        if (!$canonicalSubject) {
            $canonicalSubject = strtoupper($subjectRaw);
        }
        $displaySubject = self::SUBJECT_DISPLAY_MAP[$subjectKey] ?? $subjectRaw;

        $totalImported = 0;

        for ($r = 4; $r <= $highestRow; $r++) {
            $day = trim((string) $sheet->getCellByColumnAndRow(2, $r)->getValue());
            $rawLesson = $sheet->getCellByColumnAndRow(3, $r)->getValue();

            if ($day === null || $day === '' || $rawLesson === null) {
                continue;
            }

            $day = $this->resolveDayName($day);
            if (!$day) continue;

            $rawLesson = (int) $rawLesson;

            // Gotcha #1: Remap Jumat
            $lesson = $this->remapFridayLesson($day, $rawLesson);

            // Skip periode tidak valid (misal jam 6 di Jumat)
            if (!in_array($lesson, self::VALID_LESSONS_BY_DAY[$day] ?? [])) {
                continue;
            }

            // Baca kelas dari kolom D+ (col 4+)
            $highestCol = Coordinate::columnIndexFromString($sheet->getHighestColumn());

            for ($col = 4; $col <= $highestCol; $col++) {
                $cellValue = trim((string) $sheet->getCellByColumnAndRow($col, $r)->getValue());
                if ($cellValue === '' || $cellValue === '-') {
                    continue;
                }

                $classes = array_map('trim', explode(',', $cellValue));

                foreach ($classes as $cls) {
                    $normalizedCls = $this->normalizeClassName($cls);
                    if (!in_array($normalizedCls, $allClasses)) {
                        continue;
                    }

                    $gender = $this->genderOf($normalizedCls);
                    [$startTime, $endTime] = $this->getBellTimes($day, $lesson, $gender);

                    // Cari guru
                    $key = $normalizedCls . '|' . $canonicalSubject;
                    $teachers = $teacherMap[$key] ?? [];
                    $teacher = !empty($teachers) ? implode(', ', $teachers) : null;

                    Schedule::updateOrCreate(
                        [
                            'class_name' => $normalizedCls,
                            'day'        => $day,
                            'period'     => $lesson,
                        ],
                        [
                            'subject_display' => $displaySubject,
                            'subject'         => $canonicalSubject,
                            'teacher'         => $teacher,
                            'start_time'      => $startTime,
                            'end_time'        => $endTime,
                        ]
                    );
                    $totalImported++;
                }
            }
        }

        return $totalImported;
    }

    /**
     * Proses satu sheet Leadership dari v9.4.
     * Gotcha #2: ambil HANYA kolom pertama yang berisi data, abaikan kolom lain (duplikat).
     * Leadership entries menggunakan grade sebagai class_name (misal '7', '8', '9')
     * agar bisa ditemukan oleh JournalController dengan LIKE query.
     */
    private function processLeadershipSheet($sheet, array $participants, string $code): int
    {
        $highestRow = $sheet->getHighestRow();
        $highestCol = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        $displaySubject = self::LEADERSHIP_LABEL[$code] ?? 'Leadership';

        // Ekstrak grade dari kode sheet (L7 → '7', L8 → '8', L9 → '9')
        $grade = substr($code, 1); // 'L7' → '7', 'L8' → '8', 'L9' → '9'

        $totalImported = 0;

        for ($r = 4; $r <= $highestRow; $r++) {
            $day = trim((string) $sheet->getCellByColumnAndRow(2, $r)->getValue());
            $rawLesson = $sheet->getCellByColumnAndRow(3, $r)->getValue();

            if ($day === null || $day === '' || $rawLesson === null) {
                continue;
            }

            $day = $this->resolveDayName($day);
            if (!$day) continue;

            $rawLesson = (int) $rawLesson;
            $lesson = $this->remapFridayLesson($day, $rawLesson);

            if (!in_array($lesson, self::VALID_LESSONS_BY_DAY[$day] ?? [])) {
                continue;
            }

            // Gotcha #2: cek apakah ada data di kolom D+ (ambil kolom pertama yang tidak kosong)
            $hasData = false;
            for ($col = 4; $col <= $highestCol; $col++) {
                $cellValue = trim((string) $sheet->getCellByColumnAndRow($col, $r)->getValue());
                if ($cellValue !== '' && $cellValue !== '-') {
                    $hasData = true;
                    break;
                }
            }
            if (!$hasData) continue;

            // Ambil kelas dari kolom pertama yang berisi data (col 4 = kolom D)
            // untuk menentukan apakah slot ini aktif (ada kelas yang terdaftar)
            $firstColValue = trim((string) $sheet->getCellByColumnAndRow(4, $r)->getValue());
            if ($firstColValue === '' || $firstColValue === '-') continue;

            // Leadership tidak split gender — gunakan "boys" untuk waktu (netral)
            [$startTime, $endTime] = $this->getBellTimes($day, $lesson, 'boys');

            foreach ($participants as $nick) {
                Schedule::updateOrCreate(
                    [
                        'class_name' => $grade,
                        'day'        => $day,
                        'period'     => $lesson,
                        'teacher'    => null,
                    ],
                    [
                        'subject_display' => $displaySubject,
                        'subject'         => 'Leadership',
                        'teacher'         => null,
                        'start_time'      => $startTime,
                        'end_time'        => $endTime,
                    ]
                );
                $totalImported++;
            }
        }

        return $totalImported;
    }

    /**
     * Proses satu sheet tanpa guru (HT/Scout/SBK/SD) dari v9.4.
     * Gotcha #2: ambil HANYA kolom pertama yang berisi data.
     */
    private function processWithoutTeacherSheet($sheet, array $allClasses): int
    {
        $highestRow = $sheet->getHighestRow();
        $highestCol = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        $subjectRaw = $sheet->getTitle();
        $subjectKey = rtrim($subjectRaw, '.');
        $canonicalSubject = \App\Helpers\SubjectHelper::normalize($subjectRaw);
        if (!$canonicalSubject) {
            $canonicalSubject = strtoupper($subjectRaw);
        }
        $displaySubject = self::SUBJECT_DISPLAY_MAP[$subjectKey] ?? $subjectRaw;

        $totalImported = 0;

        for ($r = 4; $r <= $highestRow; $r++) {
            $day = trim((string) $sheet->getCellByColumnAndRow(2, $r)->getValue());
            $rawLesson = $sheet->getCellByColumnAndRow(3, $r)->getValue();

            if ($day === null || $day === '' || $rawLesson === null) {
                continue;
            }

            $day = $this->resolveDayName($day);
            if (!$day) continue;

            $rawLesson = (int) $rawLesson;
            $lesson = $this->remapFridayLesson($day, $rawLesson);

            if (!in_array($lesson, self::VALID_LESSONS_BY_DAY[$day] ?? [])) {
                continue;
            }

            // Gotcha #2: cek apakah ada data di kolom D+
            $hasData = false;
            for ($col = 4; $col <= $highestCol; $col++) {
                $cellValue = trim((string) $sheet->getCellByColumnAndRow($col, $r)->getValue());
                if ($cellValue !== '' && $cellValue !== '-') {
                    $hasData = true;
                    break;
                }
            }
            if (!$hasData) continue;

            // Ambil kelas dari kolom pertama yang berisi data
            $firstColValue = trim((string) $sheet->getCellByColumnAndRow(4, $r)->getValue());
            if ($firstColValue === '' || $firstColValue === '-') continue;

            // Parse kelas dari kolom pertama (bisa comma-separated)
            $classes = array_map('trim', explode(',', $firstColValue));

            foreach ($classes as $cls) {
                $normalizedCls = $this->normalizeClassName($cls);
                if (!in_array($normalizedCls, $allClasses)) continue;

                // Tanpa guru → teacher = null
                Schedule::updateOrCreate(
                    [
                        'class_name' => $normalizedCls,
                        'day'        => $day,
                        'period'     => $lesson,
                    ],
                    [
                        'subject_display' => $displaySubject,
                        'subject'         => $canonicalSubject,
                        'teacher'         => null,
                        'start_time'      => null,
                        'end_time'        => null,
                    ]
                );
                $totalImported++;
            }
        }

        return $totalImported;
    }

    /**
     * Proses sheet "Available teachers" dari v9.4.xlsx.
     * Format teacher-centric: Col A = hari, Col B = lesson#, Col C = guru (comma-separated).
     * Cross-reference dengan Lessons sheet untuk menentukan kelas dan mapel.
     *
     * @param  \PhpOffice\PhpSpreadsheet\Spreadsheet  $spreadsheet
     * @param  array  $allClasses
     * @param  array  $teacherToClassesSubjects  nama lengkap guru → [[kelas, mapel], ...]
     * @param  array  $teacherNickToClassesSubjects  nickname guru → [[kelas, mapel], ...]
     * @param  array  $nickToFull  nickname → nama lengkap guru
     * @return int  jumlah record yang diimport
     */
    private function processAvailableTeachersFormat($spreadsheet, array $allClasses, array $teacherToClassesSubjects, array $teacherNickToClassesSubjects, array $nickToFull = []): int
    {
        $totalImported = 0;

        // Prioritaskan sheet "Available teachers" (nama lengkap), fallback ke "Available teachers 2" (nickname)
        $sheetsToProcess = [];
        if ($spreadsheet->getSheetByName('Available teachers') !== null) {
            $sheetsToProcess[] = ['name' => 'Available teachers', 'usesNicknames' => false];
        }
        if ($spreadsheet->getSheetByName('Available teachers 2') !== null && empty($sheetsToProcess)) {
            $sheetsToProcess[] = ['name' => 'Available teachers 2', 'usesNicknames' => true];
        }

        foreach ($sheetsToProcess as $sheetConfig) {
            $sheetName = $sheetConfig['name'];
            $usesNicknames = $sheetConfig['usesNicknames'];

            $ws = $spreadsheet->getSheetByName($sheetName);
            if (!$ws) continue;

            $highestRow = $ws->getHighestRow();

            for ($r = 2; $r <= $highestRow; $r++) {
                $day = trim((string) $ws->getCellByColumnAndRow(1, $r)->getValue());
                $rawLesson = $ws->getCellByColumnAndRow(2, $r)->getValue();
                $teachersRaw = trim((string) $ws->getCellByColumnAndRow(3, $r)->getValue());

                if ($day === '' || $rawLesson === '' || $teachersRaw === '') {
                    continue;
                }

                $day = $this->resolveDayName($day);
                if (!$day) continue;

                $rawLesson = (int) $rawLesson;
                $lesson = $this->remapFridayLesson($day, $rawLesson);

                if (!in_array($lesson, self::VALID_LESSONS_BY_DAY[$day] ?? [])) {
                    continue;
                }

                // Parse nama guru dari kolom C
                $teacherNames = array_map('trim', explode(',', $teachersRaw));
                $teacherNames = array_filter($teacherNames);

                foreach ($teacherNames as $teacherName) {
                    // Lookup kelas & mapel berdasarkan format nama guru
                    if ($usesNicknames) {
                        $classesSubjects = $teacherNickToClassesSubjects[$teacherName] ?? [];
                        // Resolve nickname ke nama lengkap untuk penyimpanan
                        $teacherName = $nickToFull[$teacherName] ?? $teacherName;
                    } else {
                        $classesSubjects = $teacherToClassesSubjects[$teacherName] ?? [];
                    }

                    if (empty($classesSubjects)) {
                        continue;
                    }

                    foreach ($classesSubjects as [$cls, $subject]) {
                        if (!in_array($cls, $allClasses)) {
                            continue;
                        }

                        $gender = $this->genderOf($cls);
                        [$startTime, $endTime] = $this->getBellTimes($day, $lesson, $gender);

                        $displaySubject = self::SUBJECT_DISPLAY_MAP[$subject] ?? $subject;

                        Schedule::updateOrCreate(
                            [
                                'class_name' => $cls,
                                'day'        => $day,
                                'period'     => $lesson,
                            ],
                            [
                                'subject_display' => $displaySubject,
                                'subject'         => $subject,
                                'teacher'         => $teacherName,
                                'start_time'      => $startTime,
                                'end_time'        => $endTime,
                            ]
                        );
                        $totalImported++;
                    }
                }
            }
        }

        return $totalImported;
    }

    /**
     * Resolusi nama hari dari berbagai format ke standar Inggris.
     */
    private function resolveDayName(string $day): ?string
    {
        $upper = strtoupper(trim($day));
        return self::DAY_MAP[$upper] ?? null;
    }

    // ═══════════════════════════════════════════════════════════
    // PRIVATE HELPERS — Bel & Gender (tetap dari kode lama)
    // ═══════════════════════════════════════════════════════════

    private function genderOf(string $className): string
    {
        if (preg_match('/\d([A-F])\b/i', $className, $m)) {
            return in_array(strtoupper($m[1]), ['A', 'B', 'C']) ? 'boys' : 'girls';
        }
        return 'boys';
    }

    private function getBellTimes(string $day, int $period, string $gender): array
    {
        if ($day === 'Saturday') {
            if ($period === 3) return self::SATURDAY_BELL3[$gender];
            return self::SATURDAY_BELLS[$period] ?? [null, null];
        }
        if ($day === 'Friday') {
            if ($period === 3) return self::FRIDAY_BELL3[$gender];
            return self::FRIDAY_BELLS[$period] ?? [null, null];
        }
        if ($period === 3) return self::WEEKDAY_BELL3[$gender];
        return self::WEEKDAY_BELLS[$period] ?? [null, null];
    }

    /**
     * Preview v9.4.xlsx file contents for user verification before import.
     * Returns sheet names and first 5 rows of each sheet as JSON.
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'file_v94' => 'required|file|mimes:xlsx,xls|max:20480',
        ]);

        try {
            $v94 = IOFactory::load($request->file('file_v94')->getRealPath());
            $sheetNames = $v94->getSheetNames();
            $preview = [];

            foreach ($sheetNames as $sheetName) {
                $ws = $v94->getSheetByName($sheetName);
                if (!$ws) continue;

                $rows = [];
                $maxRows = min(5, $ws->getHighestRow());
                $maxCols = min(8, Coordinate::columnIndexFromString($ws->getHighestColumn()));

                for ($r = 1; $r <= $maxRows; $r++) {
                    $row = [];
                    for ($col = 1; $col <= $maxCols; $col++) {
                        $row[] = trim((string) $ws->getCellByColumnAndRow($col, $r)->getValue());
                    }
                    $rows[] = $row;
                }

                $preview[$sheetName] = $rows;
            }

            return response()->json([
                'status' => 'success',
                'sheets' => $preview,
                'sheetNames' => $sheetNames,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membaca file: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Normalisasi nama kelas dari v9.4 ke nama di file jadwal.
     * "7C ICT" → "7C", "7C TCP" → "7C", "8A" → "8A"
     */
    private function normalizeClassName(string $cls): string
    {
        return trim(preg_replace('/\s+(ICT|TCP|IFE)\s*$/i', '', $cls));
    }
}
