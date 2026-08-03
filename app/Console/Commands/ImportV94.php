<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Teacher;
use App\Models\Schedule;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ImportV94 extends Command
{
    protected $signature = 'v94:import 
                            {file : Path ke file v9.4.xlsx}
                            {--dry-run : Tampilkan preview tanpa menyimpan ke DB}
                            {--force : Skip konfirmasi}';
    protected $description = 'Import jadwal dari file v9.4.xlsx (aSc Timetables) ke DB';

    private const SUBJECT_SHEETS = [
        'Sprt.', 'Soc.', 'Sc.', 'Quran.', 'Math.', 'IFE.', 'ICT.', 'Eng.', 'Cv.', 'BI.',
    ];

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

    private const WITHOUT_TEACHER_SHEETS = [
        'HOMEROOM TEACHER.', 'SCOUT.', 'SENI BUDAYA KESENIAN.', 'SELF DEVELOPMENT.',
    ];

    private const SUBJECT_DISPLAY_MAP = [
        'Sprt' => 'SPORT', 'Soc' => 'Social', 'Sc' => 'Science', 'Quran' => 'Quran',
        'Math' => 'Mathematics', 'IFE' => 'IFE', 'ICT' => 'ICT', 'Eng' => 'English',
        'Cv' => 'Civics', 'BI' => 'Indonesian',
    ];

    private const VALID_LESSONS_BY_DAY = [
        'Monday'    => [1, 2, 3, 4, 5, 6, 7, 8, 9],
        'Tuesday'   => [1, 2, 3, 4, 5, 6, 7, 8, 9],
        'Wednesday' => [1, 2, 3, 4, 5, 6, 7, 8, 9],
        'Thursday'  => [1, 2, 3, 4, 5, 6, 7, 8, 9],
        'Friday'    => [1, 2, 3, 4, 5, 7, 8, 9],
        'Saturday'  => [1, 2, 3, 4, 5, 6],
    ];

    private const FRIDAY_RAW_TO_DISPLAY = [6 => 7, 7 => 8, 8 => 9];

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

    public function handle(): int
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("File tidak ditemukan: {$filePath}");
            return 1;
        }

        if (!$this->option('force') && !$this->option('dry-run')) {
            if (!$this->confirm("Import akan MENGHAPUS semua jadwal lama dan mengganti dengan data dari {$filePath}. Lanjutkan?")) {
                $this->info('Dibatalkan.');
                return 0;
            }
        }

        try {
            $this->info("Loading {$filePath}...");
            $v94 = IOFactory::load($filePath);
            $this->info('Sheets: ' . implode(', ', $v94->getSheetNames()));

            // 1. Baca data referensi
            $nicknameMap = $this->readTeachersSheet($v94);
            $lessonsResult = $this->readLessonsSheet($v94, $nicknameMap);
            $teacherMap = $lessonsResult['teacherMap'];
            $leadershipParticipants = $lessonsResult['leadershipParticipants'];
            $allClasses = $this->readClassesSheet($v94);

            $this->info('Teachers: ' . count($nicknameMap));
            $this->info('Teacher→(kelas,mapel) entries: ' . count($teacherMap));
            $this->info('Classes: ' . count($allClasses));

            // 2. Import guru
            if (!$this->option('dry-run')) {
                DB::beginTransaction();
                $this->info('Importing teachers...');
                $totalTeachers = $this->importTeachersFromV94($v94, $teacherMap);
                $this->info("Teachers saved: {$totalTeachers}");

                // 3. Hapus jadwal lama
                Schedule::query()->delete();
                $this->info('Old schedules cleared.');

                $totalImported = 0;

                // 4. Cek format Available teachers
                $hasAvailableTeachers = $v94->getSheetByName('Available teachers') !== null
                    || $v94->getSheetByName('Available teachers 2') !== null;

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
                    $totalImported += $this->processAvailableTeachersFormat(
                        $v94, $allClasses,
                        $lessonsResult['teacherToClassesSubjects'],
                        $lessonsResult['teacherNickToClassesSubjects'],
                        $nickToFull
                    );
                } else {
                    foreach (self::SUBJECT_SHEETS as $sheetname) {
                        $ws = $v94->getSheetByName($sheetname);
                        if (!$ws) continue;
                        $imported = $this->processSubjectSheet($ws, $teacherMap, $allClasses);
                        $this->info("Subject sheet {$sheetname}: {$imported} records");
                        $totalImported += $imported;
                    }
                }

                // 5. Leadership
                foreach (self::LEADERSHIP_SHEETS as $sheetname) {
                    $ws = $v94->getSheetByName($sheetname);
                    if (!$ws) continue;
                    $code = self::LEADERSHIP_CODE_OF_SHEET[$sheetname];
                    $participants = $leadershipParticipants[$code] ?? [];
                    $imported = $this->processLeadershipSheet($ws, $participants, $code);
                    $this->info("Leadership {$sheetname}: {$imported} records");
                    $totalImported += $imported;
                }

                // 6. Without-teacher sheets
                foreach (self::WITHOUT_TEACHER_SHEETS as $sheetname) {
                    $ws = $v94->getSheetByName($sheetname);
                    if (!$ws) continue;
                    $imported = $this->processWithoutTeacherSheet($ws, $allClasses);
                    $this->info("Without-teacher {$sheetname}: {$imported} records");
                    $totalImported += $imported;
                }

                DB::commit();

                $this->info("Import selesai: {$totalTeachers} guru, {$totalImported} slot jadwal.");
            } else {
                $this->warn('DRY RUN - tidak ada yang disimpan ke DB.');
                $this->info('Guru: ' . count($nicknameMap));
                $this->info('Kelas: ' . count($allClasses));
                $this->info('Teacher→(kelas,mapel) entries: ' . count($teacherMap));
                $this->info('Leadership participants: ' . json_encode($leadershipParticipants));
            }

            return 0;
        } catch (\Exception $e) {
            if (!($this->option('dry-run'))) {
                DB::rollBack();
            }
            $this->error('Error: ' . $e->getMessage());
            Log::error('v94:import error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return 1;
        }
    }

    // ───────────────────────────────────────────────────────
    // Helpers (sama dengan ScheduleController)
    // ───────────────────────────────────────────────────────

    private function readClassesSheet($spreadsheet): array
    {
        $sheet = $spreadsheet->getSheetByName('Classes');
        if (!$sheet) return [];
        $classes = [];
        foreach ($sheet->getRowIterator(2, 200) as $row) {
            $r = $row->getRowIndex();
            $cls = trim((string) $sheet->getCell([1, $r])->getValue());
            if ($cls === '') break;
            $classes[] = $this->normalizeClassName($cls);
        }
        return array_unique($classes);
    }

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

    private function readLessonsSheet($spreadsheet, array $nicknameMap): array
    {
        $sheet = $spreadsheet->getSheetByName('Lessons');
        if (!$sheet) {
            return ['teacherMap' => [], 'leadershipParticipants' => [], 'teacherToClassesSubjects' => [], 'teacherNickToClassesSubjects' => []];
        }

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

            $teachers = [];
            foreach (array_map('trim', explode(',', $teacherRaw)) as $name) {
                $teachers[] = $nicknameMap[$name] ?? $name;
            }
            $teachers = array_filter($teachers);

            $canonicalSubject = \App\Helpers\SubjectHelper::normalize($subjectRaw);

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

            $classes = array_map('trim', explode(',', $classField));
            foreach ($classes as $cls) {
                $normalizedCls = $this->normalizeClassName($cls);
                $key = $normalizedCls . '|' . $canonicalSubject;
                if (!isset($teacherOf[$key])) $teacherOf[$key] = [];
                $teacherOf[$key] = array_unique(array_merge($teacherOf[$key], $teachers));

                foreach ($teachers as $teacherNick) {
                    $teacherNickToClassesSubjects[$teacherNick][] = [$normalizedCls, $canonicalSubject];
                }
            }
        }

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

    private function importTeachersFromV94($spreadsheet, array $teacherMap): int
    {
        $sheet = $spreadsheet->getSheetByName('Teachers');
        if (!$sheet) return 0;

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

        Teacher::where('is_admin', 0)
            ->whereNotIn('email', $importedEmails)
            ->delete();

        return $count;
    }

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
            $lesson = $this->remapFridayLesson($day, $rawLesson);

            if (!in_array($lesson, self::VALID_LESSONS_BY_DAY[$day] ?? [])) {
                continue;
            }

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

    private function processLeadershipSheet($sheet, array $participants, string $code): int
    {
        $highestRow = $sheet->getHighestRow();
        $highestCol = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        $displaySubject = self::LEADERSHIP_LABEL[$code] ?? 'Leadership';
        $grade = substr($code, 1);

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

            $hasData = false;
            for ($col = 4; $col <= $highestCol; $col++) {
                $cellValue = trim((string) $sheet->getCellByColumnAndRow($col, $r)->getValue());
                if ($cellValue !== '' && $cellValue !== '-') {
                    $hasData = true;
                    break;
                }
            }
            if (!$hasData) continue;

            $firstColValue = trim((string) $sheet->getCellByColumnAndRow(4, $r)->getValue());
            if ($firstColValue === '' || $firstColValue === '-') continue;

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

            $hasData = false;
            for ($col = 4; $col <= $highestCol; $col++) {
                $cellValue = trim((string) $sheet->getCellByColumnAndRow($col, $r)->getValue());
                if ($cellValue !== '' && $cellValue !== '-') {
                    $hasData = true;
                    break;
                }
            }
            if (!$hasData) continue;

            $firstColValue = trim((string) $sheet->getCellByColumnAndRow(4, $r)->getValue());
            if ($firstColValue === '' || $firstColValue === '-') continue;

            $classes = array_map('trim', explode(',', $firstColValue));

            foreach ($classes as $cls) {
                $normalizedCls = $this->normalizeClassName($cls);
                if (!in_array($normalizedCls, $allClasses)) continue;

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

    private function processAvailableTeachersFormat($spreadsheet, array $allClasses, array $teacherToClassesSubjects, array $teacherNickToClassesSubjects, array $nickToFull = []): int
    {
        $totalImported = 0;

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

                $teacherNames = array_map('trim', explode(',', $teachersRaw));
                $teacherNames = array_filter($teacherNames);

                foreach ($teacherNames as $teacherName) {
                    if ($usesNicknames) {
                        $classesSubjects = $teacherNickToClassesSubjects[$teacherName] ?? [];
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

    private function resolveDayName(string $day): ?string
    {
        $upper = strtoupper(trim($day));
        $map = [
            'SENIN' => 'Monday', 'SELASA' => 'Tuesday', 'RABU' => 'Wednesday',
            'KAMIS' => 'Thursday', 'JUMAT' => 'Friday', "JUM'AT" => 'Friday',
            'SABTU' => 'Saturday',
            'MONDAY' => 'Monday', 'TUESDAY' => 'Tuesday', 'WEDNESDAY' => 'Wednesday',
            'THURSDAY' => 'Thursday', 'FRIDAY' => 'Friday', 'SATURDAY' => 'Saturday',
        ];
        return $map[$upper] ?? null;
    }

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

    private function normalizeClassName(string $cls): string
    {
        return trim(preg_replace('/\s+(ICT|TCP|IFE)\s*$/i', '', $cls));
    }

    private function remapFridayLesson(string $day, int $rawLesson): int
    {
        if ($day === 'Friday') {
            return self::FRIDAY_RAW_TO_DISPLAY[$rawLesson] ?? $rawLesson;
        }
        return $rawLesson;
    }
}
