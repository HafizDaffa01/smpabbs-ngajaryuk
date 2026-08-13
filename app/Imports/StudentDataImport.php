<?php

namespace App\Imports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\SkipsUnknownSheets;
use Illuminate\Support\Collection;

class StudentDataImport implements WithMultipleSheets, SkipsUnknownSheets
{
    public function sheets(): array
    {
        return [
            'LEVEL 7' => new LevelSheetParser('LEVEL 7'),
            'LEVEL 8' => new LevelSheetParser('LEVEL 8'),
            'LEVEL 9' => new LevelSheetParser('LEVEL 9'),
        ];
    }

    public function onUnknownSheet($sheetName)
    {
        // Skip REKAP, MUTASI, etc.
    }
}

class LevelSheetParser implements ToCollection
{
    private string $level;

    public function __construct(string $level)
    {
        $this->level = $level;
    }

    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) {
            return;
        }

        $existing = Student::select('name', 'grade')
            ->get()
            ->map(fn($s) => strtolower($s->name . '|' . $s->grade))
            ->flip();

        $inserts = [];
        $now = now();

        if ($this->level === 'LEVEL 7') {
            $this->parseLevel7($rows, $existing, $inserts, $now);
        } else {
            $this->parseLevel8or9($rows, $existing, $inserts, $now);
        }

        if (!empty($inserts)) {
            Student::insert(array_values($inserts));
        }
    }

    private function normalizeGrade(string $raw): string
    {
        $raw = strtoupper(str_replace(' ', '', trim($raw)));
        if (preg_match('/^([789][A-Z])/', $raw, $m)) {
            return $m[1];
        }
        return $raw;
    }

    private function parseLevel7(Collection $rows, $existing, array &$inserts, $now): void
    {
        $currentGrade = '';
        $currentProgul = '';

        foreach ($rows as $row) {
            $colA = trim((string)($row[0] ?? ''));

            if ($colA === '') {
                continue;
            }

            if (preg_match('/^Kelas\s*:\s*([789]\s*[A-Za-z]?)(?:\s|\(|$)/i', $colA, $m)) {
                $currentGrade = strtoupper(str_replace(' ', '', trim($m[1])));
                $currentProgul = '';
                continue;
            }

            if (!is_numeric($colA)) {
                continue;
            }

            $colC = trim((string)($row[2] ?? '')); // Nama
            $colD = trim((string)($row[3] ?? '')); // Program
            $colE = trim((string)($row[4] ?? '')); // Kelas

            if ($colD !== '' && preg_match('/^(ICT-L|TCP|VCP|ICT)$/i', $colD)) {
                $currentProgul = strtoupper($colD);
            }

            $name = $colC;
            $grade = $colE !== '' ? $this->normalizeGrade($colE) : $currentGrade;

            if ($name === '' || $grade === '' || !preg_match('/^[789][A-Z]$/', $grade)) {
                continue;
            }

            $key = strtolower($name . '|' . $grade);
            if (!$existing->has($key)) {
                $inserts[$key] = [
                    'name' => $name,
                    'grade' => $grade,
                    'progul' => $currentProgul !== '' ? $currentProgul : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $existing->put($key, true);
            }
        }
    }

    private function parseLevel8or9(Collection $rows, $existing, array &$inserts, $now): void
    {
        $currentGrade = '';
        $currentProgul = '';

        foreach ($rows as $row) {
            $colA = trim((string)($row[0] ?? ''));

            if ($colA === '') {
                continue;
            }

            if (preg_match('/^Kelas\s*:\s*([789]\s*[A-Za-z]?)(?:\s|\(|$)/i', $colA, $m)) {
                $currentGrade = strtoupper(str_replace(' ', '', trim($m[1])));
                $currentProgul = '';
                if (preg_match('/\(([^)]+)\)/i', $colA, $pm)) {
                    $currentProgul = strtoupper(trim($pm[1]));
                }
                continue;
            }

            if (!is_numeric((string)$colA)) {
                continue;
            }

            $nameCol = ($this->level === 'LEVEL 8') ? 3 : 4; // 0-indexed: col D=3, col E=4
            $name = trim((string)($row[$nameCol] ?? ''));

            if ($name === '' || $currentGrade === '' || !preg_match('/^[789][A-Z]$/', $currentGrade)) {
                continue;
            }

            $key = strtolower($name . '|' . $currentGrade);
            if (!$existing->has($key)) {
                $inserts[$key] = [
                    'name' => $name,
                    'grade' => $currentGrade,
                    'progul' => $currentProgul !== '' ? $currentProgul : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $existing->put($key, true);
            }
        }
    }
}
