<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ReadStudentAssignment extends Command
{
    protected $signature = 'students:read
                            {file : Path ke file Excel siswa}
                            {--level= : Filter level tertentu (7, 8, atau 9)}
                            {--limit=0 : Batasi jumlah baris yang ditampilkan (0 = semua)}';
    protected $description = 'Baca file Excel siswa dan tampilkan daftar nama siswa beserta kelasnya';

    private function normalizeGrade(string $raw): string
    {
        $raw = strtoupper(str_replace(' ', '', trim($raw)));
        if (preg_match('/^([789][A-Z])/', $raw, $m)) {
            return $m[1];
        }
        return $raw;
    }

    private function readLevel7($sheet, int $maxRows, int $limit): array
    {
        $students = [];
        $highestRow = $sheet->getHighestRow();
        $highestColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestColumn());

        for ($row = 7; $row <= $highestRow; $row++) {
            $colA = trim((string)($sheet->getCellByColumnAndRow(1, $row)->getValue() ?? ''));
            $colC = trim((string)($sheet->getCellByColumnAndRow(3, $row)->getValue() ?? ''));
            $colE = trim((string)($sheet->getCellByColumnAndRow(5, $row)->getValue() ?? ''));

            if ($colA === '' || !is_numeric($colA)) {
                continue;
            }

            $name = $colC;
            $grade = $this->normalizeGrade($colE);

            if ($name === '' || $grade === '' || !preg_match('/^[789][A-Z]$/', $grade)) {
                continue;
            }

            $students[] = ['no' => (int)$colA, 'name' => $name, 'grade' => $grade, 'program' => $colE];
        }

        return array_slice($students, 0, $limit > 0 ? $limit : count($students));
    }

    private function readLevel8or9($sheet, int $maxRows, int $limit, int $startRow, int $nameCol): array
    {
        $students = [];
        $highestRow = $sheet->getHighestRow();
        $currentGrade = '';

        for ($row = $startRow; $row <= $highestRow; $row++) {
            $colA = trim((string)($sheet->getCellByColumnAndRow(1, $row)->getValue() ?? ''));

            if ($colA === '') {
                continue;
            }

            if (preg_match('/^Kelas\s*:\s*([789]\s*[A-Za-z]?)(?:\s|\(|$)/i', $colA, $m)) {
                $currentGrade = strtoupper(str_replace(' ', '', trim($m[1])));
                continue;
            }

            $no = $sheet->getCellByColumnAndRow(1, $row)->getValue();
            if (!is_numeric((string)$no)) {
                continue;
            }

            $name = trim((string)($sheet->getCellByColumnAndRow($nameCol, $row)->getValue() ?? ''));

            if ($name === '' || !preg_match('/^[789][A-Z]$/', $currentGrade)) {
                continue;
            }

            $students[] = ['no' => (int)$colA, 'name' => $name, 'grade' => $currentGrade];
        }

        return array_slice($students, 0, $limit > 0 ? $limit : count($students));
    }

    public function handle(): void
    {
        $file = $this->argument('file');

        if (!file_exists($file)) {
            $this->error("File tidak ditemukan: {$file}");
            return;
        }

        try {
            $spreadsheet = IOFactory::load($file);
        } catch (\Throwable $e) {
            $this->error("Gagal membaca file: " . $e->getMessage());
            return;
        }

        $levelFilter = strtolower($this->option('level') ?? '');
        $limit = (int) $this->option('limit');

        $levelsToRead = [];
        if ($levelFilter !== '') {
            $levelsToRead[] = 'LEVEL ' . strtoupper($levelFilter);
        } else {
            foreach ($spreadsheet->getSheetNames() as $name) {
                if (preg_match('/^LEVEL\s*[789]$/i', $name)) {
                    $levelsToRead[] = $name;
                }
            }
        }

        if (empty($levelsToRead)) {
            $this->warn("Tidak ada sheet LEVEL 7/8/9 yang ditemukan.");
            return;
        }

        $grandTotal = 0;

        foreach ($levelsToRead as $sheetName) {
            $sheet = $spreadsheet->getSheetByName($sheetName);
            if (!$sheet) {
                continue;
            }

            $this->warn("Sheet: {$sheetName}");

            $students = [];
            if (strtoupper($sheetName) === 'LEVEL 7') {
                $students = $this->readLevel7($sheet, $sheet->getHighestRow(), $limit);
            } else {
                $startRow = (strtoupper($sheetName) === 'LEVEL 8') ? 7 : 9;
                $nameCol = (strtoupper($sheetName) === 'LEVEL 8') ? 4 : 5;
                $students = $this->readLevel8or9($sheet, $sheet->getHighestRow(), $limit, $startRow, $nameCol);
            }

            $grouped = [];
            foreach ($students as $s) {
                $grouped[$s['grade']][] = $s;
            }
            ksort($grouped);

            foreach ($grouped as $grade => $list) {
                $this->info("  Kelas {$grade} — " . count($list) . " siswa");
                foreach ($list as $s) {
                    $this->line(sprintf('    %3d. %s', $s['no'], $s['name']));
                }
                $this->newLine();
            }

            $this->info("  Total di sheet ini: " . count($students) . " siswa");
            $this->newLine();
            $grandTotal += count($students);
        }

        $this->info("Total keseluruhan: {$grandTotal} siswa");
    }
}
