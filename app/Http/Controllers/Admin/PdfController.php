<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Note;
use App\Models\Schedule;
use Carbon\Carbon;

class PdfController extends Controller
{
    /**
     * Generate Rekap KBM PDF.
     */
    public function generateRekapKBM(Request $request): Response
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '300');

        $request->validate([
            'class'    => 'required|string',
            'semester' => 'nullable|integer|in:1,2',
            'year'     => 'nullable|integer',
            'view'     => 'nullable|string|in:semester,daily',
            'date'     => 'nullable|date',
        ]);

        $grade        = strtoupper($request->class);
        $semester     = $request->semester ?? (now()->month <= 6 ? 1 : 2);
        $year         = $request->year ?? now()->year;
        $viewType     = $request->view ?? 'semester';
        $selectedDate = $request->date ?? now()->toDateString();

        if ($semester == 1) {
            $startMonth = 1;
            $endMonth   = 6;
        } else {
            $startMonth = 7;
            $endMonth   = 12;
        }

        $startDate = Carbon::create($year, $startMonth, 1)->startOfMonth();
        $endDate   = Carbon::create($year, $endMonth, 1)->endOfMonth();

        if ($viewType === 'daily') {
            $carbonDate = Carbon::parse($selectedDate);
            
            $startDate  = $carbonDate->copy()->startOfDay();
            $endDate    = $carbonDate->copy()->endOfDay();
            $startMonth = $carbonDate->month;
            $endMonth   = $carbonDate->month;
        }

        // 1. Fetch Students
        if (in_array($grade, ['7', '8', '9'])) {
            $students = Student::where('grade', 'LIKE', $grade . '%')->get();
        } else {
            $students = Student::where('grade', $grade)->get();
            if ($students->isEmpty()) {
                $students = Student::where('grade', strtolower($grade))->get();
            }
        }
        $studentIds = $students->pluck('id');

        // 2. Fetch Attendance
        $targetMonths   = range($startMonth, $endMonth);
        $attendanceData = Attendance::with('student')
            ->whereIn('student_id', $studentIds)
            ->where('year', $year)
            ->whereBetween('month', [min($targetMonths), max($targetMonths)])
            ->whereIn('value', ['S', 'I', 'A'])
            ->get();

        $absentsByDate = [];
        foreach ($attendanceData as $att) {
            $dateKey = sprintf('%04d-%02d-%02d', $att->year, $att->month, $att->day);
            $absentsByDate[$dateKey][] = [
                'name'  => $att->student->name,
                'value' => $att->value
            ];
        }

        // 3. Fetch KBM (Notes)
        $kbmData = Note::with('teacher')
            ->where('class', $grade)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy('date');

        // 4. Fetch Schedule
        $gradeLevel     = preg_replace('/[^0-9]/', '', $grade);
        $schedules      = Schedule::whereIn('class_name', [$grade, $gradeLevel])->get();
        $schedulesByDay = $schedules->groupBy('day');

        $data = [
            'grade'          => $grade,
            'semester'       => $semester,
            'year'           => $year,
            'viewType'       => $viewType,
            'selectedDate'   => $selectedDate,
            'kbmData'        => $kbmData,
            'absentsByDate'  => $absentsByDate,
            'schedulesByDay' => $schedulesByDay,
            'startMonth'     => $startMonth,
            'endMonth'       => $endMonth,
            'startDate'      => $startDate,
            'endDate'        => $endDate,
        ];

        $pdf = Pdf::loadView('pdf.rekap-kbm', $data)->setPaper('a4', 'landscape');
        
        return $pdf->stream("Rekap_KBM_{$grade}_Smes{$semester}_{$year}.pdf");
    }

    /**
     * Generate Rekap Presensi PDF.
     */
    public function generateRekapPresensi(Request $request): Response
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '300');

        $request->validate([
            'class'    => 'required|string',
            'semester' => 'nullable|integer|in:1,2',
            'year'     => 'nullable|integer',
            'view'     => 'nullable|string|in:semester,monthly',
            'month'    => 'nullable|integer|min:1|max:12',
        ]);

        $grade         = strtoupper($request->class);
        $semester      = $request->semester ?? (now()->month <= 6 ? 1 : 2);
        $year          = $request->year ?? now()->year;
        $viewType      = $request->view ?? 'semester';
        $selectedMonth = $request->month ?? now()->month;

        // Sem 1: Jan - Jun (1-6)
        if ($semester == 1) {
            $startMonthBound = 1;
            $endMonthBound   = 6;
        } else {
            $startMonthBound = 7;
            $endMonthBound   = 12;
        }

        if ($viewType == 'monthly') {
            $startMonth = $selectedMonth;
            $endMonth   = $selectedMonth;
        } else {
            $startMonth = $startMonthBound;
            $endMonth   = $endMonthBound;
        }

        // 1. Students in class
        if (in_array($grade, ['7', '8', '9'])) {
            $students = Student::where('grade', 'LIKE', $grade . '%')->orderBy('name')->get();
        } else {
            $students = Student::where('grade', $grade)->orderBy('name')->get();
            if ($students->isEmpty()) {
                $students = Student::where('grade', strtolower($grade))->orderBy('name')->get();
            }
        }
        $studentIds = $students->pluck('id');

        // 2. Fetch Attendance (semester scope for total summary)
        $attendances = Attendance::whereIn('student_id', $studentIds)
            ->where('year', $year)
            ->whereBetween('month', [$startMonthBound, $endMonthBound])
            ->get();

        $attendanceMap = [];
        $summary       = [];
        
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

        $data = [
            'grade'         => $grade,
            'semester'      => $semester,
            'year'          => $year,
            'students'      => $students,
            'attendanceMap' => $attendanceMap,
            'summary'       => $summary,
            'startMonth'    => $startMonth,
            'endMonth'      => $endMonth,
            'viewType'      => $viewType,
            'selectedMonth' => $selectedMonth,
        ];

        $pdf = Pdf::loadView('pdf.rekap-presensi', $data)->setPaper('a4', 'landscape');
        
        return $pdf->stream("Rekap_Presensi_{$grade}_Smes{$semester}_{$year}.pdf");
    }
}
