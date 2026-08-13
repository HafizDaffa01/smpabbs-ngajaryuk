<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Note;
use App\Exports\JournalExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Schedule;

class JournalController extends Controller
{
    public function admin()
    {
        return view('admin.tsmanager'); // ganti dengan nama blade kamu
    }

    public function export(Request $request)
    {
        $grade = $request->class;
        $month = $request->month;
        $year  = $request->year;
        $day   = $request->day;

        // Buat tanggal lengkap
        $date = Carbon::createFromDate($year, $month, $day);

        // === DATA ABSENSI ===
        if (in_array($grade, ['7', '8', '9'])) {
            $students = Student::where('grade', 'LIKE', $grade . '%')->get();
        } else {
            $students = Student::where('grade', $grade)->get();
        }

        $attendance = Attendance::whereIn('student_id', $students->pluck('id'))
            ->where('month', $month)
            ->where('year', $year)
            ->get()
            ->groupBy('student_id')
            ->map(fn($i) => $i->pluck('value', 'day'));

        $summary = [];
        foreach ($attendance as $sid => $days) {
            $summary[$sid] = [
                'S' => $days->filter(fn($v) => $v === 'S')->count(),
                'I' => $days->filter(fn($v) => $v === 'I')->count(),
                'A' => $days->filter(fn($v) => $v === 'A')->count(),
            ];
        }

        // === KBM ===
        $notes = Note::with('teacher')->where('class', $grade)
            ->where('date', $date->toDateString())
            ->get()
            ->map(fn($n) => [
                'subject' => $n->subject,
                'teacher' => $n->teacher->name ?? $n->subject, // fallback to subject name if no teacher linked
                'time'    => $n->time,
                'note'    => $n->note,
            ]);

        // === PACK DATA ===
        $data = [
            'grade'   => $grade,
            'month'   => $month,
            'year'    => $year,
            'date'    => $date->toDateString(),
            'notes'   => $notes,
            'students'=> $students->map(fn($s) => [
                'name'       => $s->name,
                'attendance' => $attendance[$s->id] ?? [],
                'summary'    => $summary[$s->id] ?? ['S'=>0,'I'=>0,'A'=>0],
            ]),
        ];

        $code = strtoupper(substr(str_replace('-', '', (string) Str::uuid()), 0, 7));

        // Format nama file: "Jurnal 7A @ 15 December 2025#CODE.xlsx"
        $fileName = "Jurnal {$grade} @ {$date->format('d F Y')}#{$code}.xlsx";

        return Excel::download(new JournalExport($data), $fileName);
    }

    /**
     * Halaman pilih kelas
     */
    public function selectClass()
    {
        $usr = Auth::user()->id;

        // Ambil semua kelas dari Schedule
        $rawClasses = Schedule::distinct()->pluck('class_name');
        
        $classes = [];
        foreach ($rawClasses as $c) {
             // Asumsi format angka + huruf (7A, 8B, dst)
             $grade = substr($c, 0, 1);
             $sub = substr($c, 1);
             $classes[$grade][] = $sub;
        }

        // Hapus duplikat dan sort
        foreach ($classes as $grade => $subs) {
            $classes[$grade] = collect($subs)->unique()->sort()->values()->toArray();
        }

        return view('journal.select-class', compact('classes','usr'));
    }


    public function saveNote(Request $request)
    {
        $validated = $request->validate([
            'class'     => 'required|string',
            'subject'   => 'required|string',
            'teacher_id'=> 'nullable|integer',
            'date'      => 'nullable|date',
            'time'      => 'required|string',
            'note'      => 'required|string',
            'checked'   => 'required|boolean',
        ]);

        $note = Note::updateOrCreate(
            [
                'class'   => $validated['class'],
                'subject' => $validated['subject'],
                'date'    => $validated['date'],
            ],
            [
                'time'      => $validated['time'],
                'note'      => $validated['note'],
                'checked'   => $validated['checked'],
                'teacher_id'=> $validated['teacher_id'],
            ]
        );

        return response()->json(['success' => true, 'note' => $note]);
    }


    /**
     * Menampilkan jurnal berdasarkan kelas yang dipilih
     */
    public function showClass(Request $request)
    {
        $grade = strtolower($request->class); // jadi "7a"
        // Ensure grade is uppercase for Schedule query matching if needed, but selectClass logic produced 7A, 7B.
        // Wait, selectClass split '7A' -> '7', 'A'.
        // The URL param ?class=7a might be passed as lowercase from somewhere?
        // Let's normalize grade to what Schedule uses.
        // Schedule uses '7A' (class_name).
        // If request is '7a', we should uppercase it.
        $grade = strtoupper($request->class); 
        
        $usr   = $request->usr;

        // ============================
        // 0. Ambil tanggal, bulan, tahun
        // ============================
        $day   = $request->day   ?? now()->day;
        $month = $request->month ?? now()->month;
        $year  = $request->year  ?? now()->year;


        // ============================
        // 1. Ambil siswa per kelas
        // ============================
        // Student grade might be "7A" or "7a". Let's assume database is consistent or we check.
        // Existing code used strtolower($request->class). Let's check Student model usages.
        // Student::where('grade', $grade).
        // I will use the same casing as Schedule for the Schedule query.
        
        if (in_array($grade, ['7', '8', '9'])) {
            $students = Student::where('grade', 'LIKE', $grade . '%')->get();
        } else {
            $students = Student::where('grade', $grade)->get();
            if ($students->isEmpty()) {
                // Try lowercase if uppercase failed, just in case students table uses lowercase
                $students = Student::where('grade', strtolower($grade))->get();
            }
        }


        // ============================
        // 2. Ambil guru yang mengajar kelas ini DARI SCHEDULE (Filtered by Day)
        // ============================
        
        $dayOfWeek = Carbon::create($year, $month, $day)->format('l');

        if (in_array($grade, ['7', '8', '9'])) {
            // Untuk Leadership Class: ambil SEMUA jadwal di level tersebut yang mapelnya Leadership
            $schedules = Schedule::where('class_name', 'LIKE', $grade . '%')
                ->where(function ($q) {
                    $q->where('subject', 'Leadership')
                        ->orWhere('subject_display', 'LIKE', '%Leadership%');
                })
                ->where('day', $dayOfWeek)
                ->orderBy('period')
                ->get();
        } else {
            // Ambil jadwal untuk kelas ini DI HARI INI
            $schedules = Schedule::where('class_name', $grade)
                ->where('day', $dayOfWeek)
                ->orderBy('period')
                ->get();
        }
        
        // === LOGIKA PENGUMPULAN GURU ===
        $teachers = collect();
        $seen = [];

        // Pre-fetch relevant teachers from Users table for names found in schedules
        $scheduleTeacherNames = $schedules->pluck('teacher')->filter(fn($n) => !empty($n) && $n !== '-')->unique();
        $preFetchedTeachers = Teacher::whereIn('name', $scheduleTeacherNames)->get()->keyBy(fn($t) => strtolower(trim($t->name)));

        if (in_array($grade, ['7', '8', '9'])) {
            if ($schedules->isNotEmpty()) {
                // 1. Ambil guru dari Tabel Users yang mengajar Leadership
                $fromUsers = Teacher::whereRaw('LOWER(mapel) LIKE ?', ['%leadership%'])->get();
                foreach ($fromUsers as $t) {
                    $m = (array)$t->mapel;
                    $isMatchingLevel = false;

                    foreach ($m as $k => $v) {
                        $kStr = strtolower((string)$k);
                        $hasLeadershipWord = false;
                        if (is_array($v)) {
                            foreach($v as $sv) { if (is_string($sv) && str_contains(strtolower($sv), 'leadership')) $hasLeadershipWord = true; }
                        } elseif (is_string($v) && str_contains(strtolower($v), 'leadership')) {
                            $hasLeadershipWord = true;
                        }

                        if ($hasLeadershipWord) {
                            if (Str::startsWith($kStr, strtolower($grade)) || (is_string($v) && str_contains(strtolower($v), strtolower($grade)))) {
                                $isMatchingLevel = true;
                            } elseif (is_array($v)) {
                                foreach($v as $sv) { if (is_string($sv) && str_contains(strtolower($sv), strtolower($grade))) $isMatchingLevel = true; }
                            }
                            
                            if (!$isMatchingLevel) {
                                $anyGradeMentioned = false;
                                $searchStr = $kStr . (is_string($v) ? strtolower($v) : serialize($v));
                                if (str_contains($searchStr, '7') || str_contains($searchStr, '8') || str_contains($searchStr, '9')) {
                                    $anyGradeMentioned = true;
                                }
                                if (!$anyGradeMentioned) $isMatchingLevel = true; 
                            }
                            if ($isMatchingLevel) break;
                        }
                    }

                    if ($isMatchingLevel) {
                        $nameKey = strtolower(trim($t->name));
                        if (!isset($seen[$nameKey])) {
                            $t->mapel = [$grade => 'Leadership'];
                            $teachers->push($t);
                            $seen[$nameKey] = true;
                        }
                    }
                }

                // 2. Ambil dari Jadwal (Schedules) - Optimized batch fetch
                foreach ($schedules as $sched) {
                    $tName = trim($sched->teacher ?? '');
                    if (empty($tName) || $tName === '-') continue;

                    $nameKey = strtolower($tName);
                    if (!isset($seen[$nameKey])) {
                        $t = $preFetchedTeachers->get($nameKey) ?? new Teacher(['name' => strtoupper($tName)]);
                        if ($t->exists) $t = clone $t;
                        
                        $t->mapel = [$grade => 'Leadership'];
                        $teachers->push($t);
                        $seen[$nameKey] = true;
                    }
                }
            }
        } else {
            // KELAS REGULER: Tetap gunakan data Jadwal murni - Optimized batch fetch
            foreach ($schedules as $sched) {
                $tName = trim($sched->teacher ?? '');
                $subj = trim($sched->subject_display ?? '');

                if (empty($tName) && str_contains($subj, '-')) {
                    $parts = explode('-', $subj, 2);
                    $subj = trim($parts[0]);
                    $tName = trim($parts[1]);
                }

                if (empty($tName) || $tName === '-') continue;

                $nameKey = strtolower($tName);
                if (!isset($seen[$nameKey])) {
                    $t = $preFetchedTeachers->get($nameKey) ?? new Teacher(['name' => strtoupper($tName)]);
                    if ($t->exists) $t = clone $t;

                    $t->mapel = [$grade => $subj];
                    $teachers->push($t);
                    $seen[$nameKey] = true;
                }
            }
        }

        // Fallback: If no schedule for this day, maybe show all teachers who teach this class?
        // Or just leave it empty. The user said "sesuai jadwal", so empty is likely correct.

        
        // Restore $grade to what View expects if necessary?
        // View uses $grade variable.
        // Existing code: $grade = strtolower($request->class).
        // If I make it upper, then view will display "Jurnal Kelas 7A".
        // And use $t->mapel['7A'].
        // It should be fine as long as consistency is there.

        // ============================
        // 3. Ambil mapel + guru per mapel
        // ============================
        $mapelList = [];   // format: [ 'MTK' => ['guru1', 'guru2'] ]

        foreach ($teachers as $t) {
            if (!isset($t->mapel[$grade])) continue;

            $subjects = $t->mapel[$grade];

            // Kalau cuma 1 mapel (string)
            if (is_string($subjects)) {
                $mapelList[$subjects][] = $t->name;
            }

            // Kalau banyak mapel (array)
            elseif (is_array($subjects)) {
                foreach ($subjects as $sub) {
                    if ($sub) {
                        $mapelList[$sub][] = $t->name;
                    }
                }
            }
        }


        // Month, Year, Day initialized at the top



        // ============================
        // 5. Ambil absensi berdasarkan student_id
        // ============================
        $studentIds = $students->pluck('id');

        $attendance = Attendance::whereIn('student_id', $studentIds)
            ->where('month', $month)
            ->where('year', $year)
            ->get()
            ->groupBy('student_id')
            ->map(function ($items) {
                return $items->pluck('value', 'day');
            });


        // ============================
        // 6. Summary S / I / A
        // ============================
        $summary = [];
        foreach ($attendance as $sid => $days) {
            $summary[$sid] = [
                'S' => $days->filter(fn($v) => $v === 'S')->count(),
                'I' => $days->filter(fn($v) => $v === 'I')->count(),
                'A' => $days->filter(fn($v) => $v === 'A')->count(),
            ];
        }

        // Day initialized at the top


        $selectedDate = $year . '-' .
            str_pad($month,2,'0',STR_PAD_LEFT) . '-' .
            str_pad($day,2,'0',STR_PAD_LEFT);

        $noteList = Note::where('class', $grade)
            ->where('date', $selectedDate)
            ->get();

        $noteIndexed = $noteList->keyBy('subject');


        // ============================
        // 7. Return ke view
        // ============================
        return view('journal.class-detail', [
            'grade'       => $grade,
            'students'    => $students,
            'teachers'    => $teachers,
            'mapelList'   => $mapelList,
            'attendance'  => $attendance,
            'summary'     => $summary,
            'month'       => $month,
            'currentMonth'=> $month,
            'year'        => $year,
            'day'         => $day,
            'usr'         => $usr,
            'noteList'    => $noteList,
            'noteIndexed' => $noteIndexed,
            'schedules'   => $schedules // Tambahkan ini
        ]);

    }

    /**
     * API Simpan semua kehadiran
     */
    public function saveAll(Request $request)
    {
        $request->validate([
            'month'      => 'required|integer',
            'year'       => 'required|integer',
            'attendance' => 'nullable|array',
            'kbm'        => 'nullable|array',
        ]);

        // Ambil class dari query URL
        $class = $request->query('class') ?? null;

        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $class) {
            // ================= ABSENSI =================
            if ($request->attendance) {
                foreach ($request->attendance as $r) {
                    Attendance::updateOrCreate(
                        [
                            'student_id' => $r['student_id'],
                            'day'        => $r['day'],
                            'month'      => $request->month,
                            'year'       => $request->year,
                        ],
                        ['value' => $r['value']]
                    );
                }
            }

            // ================= KBM / NOTES =================
            if ($request->kbm) {
                foreach ($request->kbm as $k) {
                    // Validasi minimal
                    if (!isset($k['subject'], $k['date'], $k['time'], $k['note'])) continue;

                    Note::updateOrCreate(
                        [
                            'class'   => $class,
                            'subject' => $k['subject'],
                            'date'    => $k['date'],
                        ],
                        [
                            'teacher_id' => $k['teacher_id'] ?? null,
                            'time'       => $k['time'],
                            'note'       => $k['note'],
                            'checked'    => true, // selalu true
                        ]
                    );
                }
            }
        });

        return response()->json(['success' => true]);
    }

    public function rekapIndex()
    {
        $usr = Auth::user()->id;
        $rawClasses = Schedule::distinct()->pluck('class_name');
        
        $classes = [];
        foreach ($rawClasses as $c) {
             $grade = substr($c, 0, 1);
             $sub = substr($c, 1);
             $classes[$grade][] = $sub;
        }

        foreach ($classes as $grade => $subs) {
            $classes[$grade] = collect($subs)->unique()->sort()->values()->toArray();
        }

        return view('journal.rekap-select-class', compact('classes','usr'));
    }

    public function showRekap(Request $request)
    {
        $grade = strtoupper($request->class);
        $viewType = $request->view ?? 'semester'; // 'semester' or 'daily'
        $semester = $request->semester ?? (now()->month <= 6 ? 1 : 2);
        $year = $request->year ?? now()->year;
        $selectedDate = $request->date ?? now()->toDateString();

        // Sem 1: Jan - Jun (1-6)
        // Sem 2: Jul - Dec (7-12)
        if ($semester == 1) {
            $startMonth = 1;
            $endMonth = 6;
        } else {
            $startMonth = 7;
            $endMonth = 12;
        }

        $carbonDate = Carbon::parse($selectedDate);

        if ($viewType === 'daily') {
            $startDate = $carbonDate->toDateString();
            $endDate = $carbonDate->toDateString();
            $targetMonths = [$carbonDate->month];
        } else {
            $startDate = Carbon::create($year, $startMonth, 1)->toDateString();
            $endDate = Carbon::create($year, $endMonth, ($endMonth == 6 ? 30 : 31))->toDateString();
            $targetMonths = range($startMonth, $endMonth);
        }

        // Students in class
        if (in_array($grade, ['7', '8', '9'])) {
            $students = Student::where('grade', 'LIKE', $grade . '%')->get();
        } else {
            $students = Student::where('grade', $grade)->get();
            if ($students->isEmpty()) {
                $students = Student::where('grade', strtolower($grade))->get();
            }
        }
        $studentIds = $students->pluck('id');

        // Fetch Attendance
        $attendanceData = Attendance::with('student')
            ->whereIn('student_id', $studentIds)
            ->where('year', $year)
            ->whereBetween('month', [min($targetMonths), max($targetMonths)])
            ->whereIn('value', ['S', 'I', 'A'])
            ->when($viewType === 'daily', function($q) use ($carbonDate) {
                return $q->where('day', $carbonDate->day);
            })
            ->get();

        $absentsByDate = [];
        foreach ($attendanceData as $att) {
            $dateKey = sprintf('%04d-%02d-%02d', $att->year, $att->month, $att->day);
            $absentsByDate[$dateKey][] = [
                'name' => $att->student->name,
                'value' => $att->value
            ];
        }

        // Fetch KBM (Notes)
        $kbmData = Note::with('teacher')
            ->where('class', $grade)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy('date');

        // Fetch Schedule (Support specific class e.g. '7A' or grade level e.g. '7')
        $gradeLevel = preg_replace('/[^0-9]/', '', $grade); // Extract number like '7' from '7A'
        
        $schedules = Schedule::whereIn('class_name', [$grade, $gradeLevel])
            ->get();
            
        $schedulesByDay = $schedules->groupBy('day');

        return view('journal.rekap-semester', [
            'grade' => $grade,
            'semester' => $semester,
            'year' => $year,
            'viewType' => $viewType,
            'selectedDate' => $selectedDate,
            'kbmData' => $kbmData,
            'absentsByDate' => $absentsByDate,
            'schedulesByDay' => $schedulesByDay,
            'startMonth' => $startMonth,
            'endMonth' => $endMonth,
        ]);
    }

    public function showRekapPresensi(Request $request)
    {
        $grade = strtoupper($request->class);
        $semester = $request->semester ?? (now()->month <= 6 ? 1 : 2);
        $year = $request->year ?? now()->year;
        $viewType = $request->view ?? 'semester';
        $selectedMonth = $request->month ?? now()->month;

        // Sem 1: Jan - Jun (1-6)
        // Sem 2: Jul - Dec (7-12)
        if ($semester == 1) {
            $startMonthBound = 1;
            $endMonthBound = 6;
        } else {
            $startMonthBound = 7;
            $endMonthBound = 12;
        }

        if ($viewType == 'monthly') {
            $startMonth = $selectedMonth;
            $endMonth = $selectedMonth;
        } else {
            $startMonth = $startMonthBound;
            $endMonth = $endMonthBound;
        }

        // Students in class
        if (in_array($grade, ['7', '8', '9'])) {
            $students = Student::where('grade', 'LIKE', $grade . '%')->orderBy('name')->get();
        } else {
            $students = Student::where('grade', $grade)->orderBy('name')->get();
            if ($students->isEmpty()) {
                $students = Student::where('grade', strtolower($grade))->orderBy('name')->get();
            }
        }
        $studentIds = $students->pluck('id');

        // Fetch Attendance for the whole semester range (to keep summary accurate even in monthly view)
        $attendances = Attendance::whereIn('student_id', $studentIds)
            ->where('year', $year)
            ->whereBetween('month', [$startMonthBound, $endMonthBound])
            ->get();

        // Organize attendance by [student_id][month][day]
        $attendanceMap = [];
        $summary = [];

        foreach ($students as $s) {
            $summary[$s->id] = ['S' => 0, 'I' => 0, 'A' => 0];
        }

        foreach ($attendances as $att) {
            $val = strtoupper($att->value);
            $attendanceMap[$att->student_id][$att->month][$att->day] = $val;
            
            if (isset($summary[$att->student_id][$val])) {
                $summary[$att->student_id][$val]++;
            }
        }

        return view('journal.rekap-presensi', [
            'grade' => $grade,
            'semester' => $semester,
            'year' => $year,
            'students' => $students,
            'attendanceMap' => $attendanceMap,
            'summary' => $summary,
            'startMonth' => $startMonth,
            'endMonth' => $endMonth,
            'viewType' => $viewType,
            'selectedMonth' => $selectedMonth,
        ]);
    }
}
