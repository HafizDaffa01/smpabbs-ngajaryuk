<?php

namespace App\Imports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\SkipsUnknownSheets;
use Illuminate\Support\Collection;

class StudentsMultiSheetImport implements WithMultipleSheets, SkipsUnknownSheets
{
    public function sheets(): array
    {
        $sheets = [];
        for ($i = 0; $i < 20; $i++) {
            $sheets[$i] = new SingleSheetImport();
        }
        return $sheets;
    }

    public function onUnknownSheet($sheetName)
    {
        // Skip sheet if out of bounds or not needed
    }
}

class SingleSheetImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) {
            return;
        }

        $existingStudents = Student::select('name', 'grade')
            ->get()
            ->map(fn($s) => strtolower($s->name . '|' . $s->grade))
            ->flip();

        $inserts = [];
        $now = now();
        $currentHeaderGrade = '';

        foreach ($rows as $row) {
            $colA = trim((string)($row[0] ?? ''));
            $colB = trim((string)($row[1] ?? ''));
            $colC = trim((string)($row[2] ?? ''));
            $colD = trim((string)($row[3] ?? ''));
            $colE = trim((string)($row[4] ?? ''));

            // 1. Detect section grade header e.g. "Kelas : 8A", "Kelas : 9 A (ICT)"
            if (preg_match('/^Kelas\s*:\s*([789]\s*[A-Za-z0-9]+)/i', $colA, $m)) {
                $rawGradeStr = strtoupper(str_replace(' ', '', trim($m[1])));
                // Clean grade string e.g. 9A(ICT) -> 9A
                if (preg_match('/^([789][A-Z])/', $rawGradeStr, $gm)) {
                    $currentHeaderGrade = $gm[1];
                } else {
                    $currentHeaderGrade = $rawGradeStr;
                }
            }

            $name = '';
            $grade = '';

            // 2. Check Format B (LEVEL 7 sheet: Col C = Nama Lengkap, Col E = Kelas e.g. 7A, 7C ICT)
            if (preg_match('/^[789][A-Z]/i', $colE) && !is_numeric($colC) && strlen($colC) > 2) {
                $name = $colC;
                if (preg_match('/^([789][A-Z])/i', strtoupper(str_replace(' ', '', $colE)), $gm)) {
                    $grade = $gm[1];
                } else {
                    $grade = strtoupper(str_replace(' ', '', $colE));
                }
            }

            // 3. Check Format with Section Header (LEVEL 8 & 9)
            if ($name === '' && $currentHeaderGrade !== '' && is_numeric($colA)) {
                // In LEVEL 9: Col E (index 4) = NAMA, Col B/C = NIS/BRIVA
                // In LEVEL 8: Col D (index 3) = NAMA SISWA
                if (!is_numeric($colE) && strlen($colE) > 2 && !in_array(strtolower($colE), ['nama', 'nama siswa', 'nama lengkap', 'asal'])) {
                    $name = $colE;
                    $grade = $currentHeaderGrade;
                } elseif (!is_numeric($colD) && strlen($colD) > 2 && !in_array(strtolower($colD), ['nama', 'nama siswa', 'nama lengkap', 'nisn', 'asal'])) {
                    $name = $colD;
                    $grade = $currentHeaderGrade;
                } elseif (!is_numeric($colC) && strlen($colC) > 2 && !in_array(strtolower($colC), ['nama', 'nama siswa', 'nama lengkap', 'briva', 'nisn'])) {
                    $name = $colC;
                    $grade = $currentHeaderGrade;
                }
            }

            // 4. Check Format A (Standard sheet: Col B = Nama, Col C = Kelas)
            if ($name === '' && preg_match('/^[789][A-Za-z0-9\s]+$/', $colC) && !is_numeric($colB) && strlen($colB) > 2) {
                $name = $colB;
                if (preg_match('/^([789][A-Za-z])/', strtoupper(str_replace(' ', '', $colC)), $gm)) {
                    $grade = $gm[1];
                } else {
                    $grade = strtoupper(str_replace(' ', '', $colC));
                }
            }

            // Clean up grade format (e.g. 7A ICT -> 7A)
            if (preg_match('/^([789][A-Z])/', $grade, $gm)) {
                $grade = $gm[1];
            }

            // Skip invalid row / headers / unwanted strings
            if ($name === '' || $grade === '' || is_numeric($name) ||
                in_array(strtolower($name), ['nama', 'nama siswa', 'nama lengkap', 'name', 'nama lengkap ananda', 'no', 'daftarsiswa', 'smp abbs surakarta', 'wali kelas']) || 
                in_array(strtolower($grade), ['kelas', 'grade', 'program']) ||
                !preg_match('/^[789][A-Z]/', $grade)
            ) {
                continue;
            }

            $key = strtolower($name . '|' . $grade);

            if (!$existingStudents->has($key)) {
                $inserts[$key] = [
                    'name'  => $name,
                    'grade' => $grade,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                
                $existingStudents->put($key, true);
            }
        }

        if (!empty($inserts)) {
            Student::insert(array_values($inserts));
        }
    }
}

