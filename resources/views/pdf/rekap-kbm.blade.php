<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekap KBM {{ $grade }} - Semester {{ $semester }} - Tahun {{ $year }}</title>
    <style>
        @page {
            margin: 1cm;
        }
        body {
            font-family: 'Helvetica', sans-serif;
            font-size: 10px;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 16px;
            letter-spacing: 2px;
        }
        .header h2 {
            margin: 3px 0;
            font-size: 11px;
            color: #555;
        }
        .header h3 {
            margin: 3px 0;
            font-size: 10px;
            text-decoration: underline;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            table-layout: fixed;
        }
        th, td {
            border: 1px solid #444;
            padding: 8px 6px;
            vertical-align: top;
            word-wrap: break-word;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
            font-size: 9px;
        }
        .date-cell {
            width: 90px;
            font-weight: bold;
            background-color: #fafafa;
        }
        .subject-cell {
            width: 100px;
        }
        .teacher-cell {
            width: 110px;
        }
        .note-cell {
            text-align: justify;
            line-height: 1.4;
        }
        .absent-cell {
            width: 130px;
        }
        .badge-absent {
            display: inline-block;
            padding: 2px 4px;
            margin: 2px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            border: 1px solid #999;
        }
        .s { background-color: #e0faff; color: #006070; }
        .i { background-color: #fff8e0; color: #705000; }
        .a { background-color: #ffe0e0; color: #700000; }
        
        .page-break {
            page-break-after: always;
        }
        .header-small { font-size: 9px; margin: 2px 0; }
        .all-present { color: #28a745; font-size: 8px; }
    </style>
</head>
<body>

    @php
        $academicYear = ($semester == 2) ? $year . '/' . ($year + 1) : ($year - 1) . '/' . $year;
        
        $dayList = [];
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            if ($date->dayOfWeek !== \Carbon\Carbon::SUNDAY) {
                $dayList[] = $date->copy();
            }
        }
        $chunks = array_chunk($dayList, 2);
    @endphp

    @foreach ($chunks as $chunkIndex => $chunk)
        <div class="header">
            <h1>JOURNAL OF SUBJECT</h1>
            <h2>ABBS JUNIOR HIGH SCHOOL</h2>
            <h3>TEACHING JOURNAL - CLASS {{ $grade }}</h3>
            <p class="header-small">Semester: {{ $semester }} | Academic Year: {{ $academicYear }} | Page: {{ $chunkIndex + 1 }}</p>
        </div>

        <table>
            <thead>
                <tr>
                    <th class="date-cell">DAY / DATE</th>
                    <th class="subject-cell">SUBJECT</th>
                    <th class="teacher-cell">TEACHER</th>
                    <th class="note-cell">LEARNING ACTIVITIES / NOTES</th>
                    <th class="absent-cell">STUDENT ABSENCES</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($chunk as $date)
                    @php
                        $dateKey = $date->toDateString();
                        $dayNotes = $kbmData->get($dateKey, collect());
                        $dayAbsents = $absentsByDate[$dateKey] ?? [];
                        $daySchedules = $schedulesByDay->get($date->translatedFormat('l'), collect());

                        $rowsToShow = [];
                        if ($daySchedules->isNotEmpty()) {
                            foreach ($daySchedules as $sched) {
                                $subjDisplay = $sched->subject_display ?: $sched->subject;
                                $matchingNote = $dayNotes->firstWhere('subject', $subjDisplay);
                                $rowsToShow[] = [
                                    'subject' => $subjDisplay,
                                    'teacher' => $sched->teacher ?: $matchingNote->teacher->name ?? '-',
                                    'note' => $matchingNote->note ?? null,
                                ];
                            }
                            foreach ($dayNotes as $dn) {
                                if (!$daySchedules->contains(fn($s) => ($s->subject_display ?: $s->subject) == $dn->subject)) {
                                    $rowsToShow[] = [
                                        'subject' => $dn->subject,
                                        'teacher' => $dn->teacher->name ?? '-',
                                        'note' => $dn->note,
                                    ];
                                }
                            }
                        } elseif ($dayNotes->isNotEmpty()) {
                            foreach ($dayNotes as $dn) {
                                $rowsToShow[] = [
                                    'subject' => $dn->subject,
                                    'teacher' => $dn->teacher->name ?? '-',
                                    'note' => $dn->note,
                                ];
                            }
                        }

                        // Fallback fallback to show blank row if no data so the day still appears
                        if (empty($rowsToShow)) {
                            $rowsToShow[] = [
                                'subject' => '-',
                                'teacher' => '-',
                                'note' => null,
                            ];
                        }

                        $rowCount = count($rowsToShow);
                    @endphp

                    @foreach ($rowsToShow as $idx => $row)
                        <tr>
                            @if ($idx === 0)
                                <td class="date-cell" rowspan="{{ $rowCount }}">
                                    {{ $date->getTranslatedDayName() }}<br>
                                    {{ $date->translatedFormat('d F Y') }}
                                </td>
                            @endif
                            <td class="subject-cell">{{ $row['subject'] }}</td>
                            <td class="teacher-cell">{{ $row['teacher'] }}</td>
                            <td class="note-cell">
                                {!! nl2br(e($row['note'] ?? '-')) !!}
                            </td>
                            @if ($idx === 0)
                                <td class="absent-cell" rowspan="{{ $rowCount }}">
                                    @forelse($dayAbsents as $abs)
                                        <div class="badge-absent {{ strtolower($abs['value']) }}">
                                            {{ $abs['name'] }} ({{ $abs['value'] }})
                                        </div>
                                    @empty
                                        <span class="all-present">All Present</span>
                                    @endforelse
                                </td>
                            @endif
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>

        @if (!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach

</body>
</html>
