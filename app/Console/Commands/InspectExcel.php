<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class InspectExcel extends Command
{
    protected $signature = 'inspect:excel
                            {file : Path ke file Excel}
                            {--sheet= : Nama sheet tertentu (default: semua)}
                            {--rows=20 : Jumlah baris yang ditampilkan per sheet}';
    protected $description = 'Baca dan tampilkan struktur file Excel (sheet, headers, sample rows)';

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

        $this->info("File: " . basename($file));
        $this->info("Sheets: " . $spreadsheet->getSheetCount());
        $this->newLine();

        $maxRows = (int) $this->option('rows');
        $targetSheet = $this->option('sheet');

        foreach ($spreadsheet->getSheetNames() as $sheetName) {
            if ($targetSheet && strcasecmp($sheetName, $targetSheet) !== 0) {
                continue;
            }

            $sheet = $spreadsheet->getSheetByName($sheetName);
            $highestRow = $sheet->getHighestRow();
            $highestColumn = $sheet->getHighestColumn();
            $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

            $this->warn("Sheet: {$sheetName}");
            $this->line("  Dimensi: {$highestRow} rows x {$highestColumn} cols");

            $displayRows = min($maxRows, $highestRow);
            for ($row = 1; $row <= $displayRows; $row++) {
                $cells = [];
                for ($col = 1; $col <= $highestColumnIndex; $col++) {
                    $cellValue = $sheet->getCellByColumnAndRow($col, $row)->getValue();
                    $cells[] = is_null($cellValue) ? '' : (string) $cellValue;
                }
                $this->line('  R' . $row . ': ' . implode(' | ', $cells));
            }

            if ($highestRow > $maxRows) {
                $this->line("  ... ({$highestRow} total rows)");
            }

            $this->newLine();
        }
    }
}
