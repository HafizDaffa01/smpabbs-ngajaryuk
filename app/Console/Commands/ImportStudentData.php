<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\StudentDataImport;

class ImportStudentData extends Command
{
    protected $signature = 'students:import-data
                            {file : Path ke file Excel siswa (Data Siswa SMP ABBS TP 2026 2027.xlsx)}
                            {--dry-run : Tampilkan preview tanpa menyimpan ke DB}
                            {--force : Skip konfirmasi}';
    protected $description = 'Import data siswa dari file Excel (LEVEL 7/8/9) ke database';

    public function handle(): void
    {
        $file = $this->argument('file');

        if (!file_exists($file)) {
            $this->error("File tidak ditemukan: {$file}");
            return;
        }

        $dryRun = $this->option('dry-run');

        if (!$dryRun && !$this->option('force')) {
            if (!$this->confirm("Import siswa dari " . basename($file) . " ke database? Siswa yang sudah ada akan dilewati.", true)) {
                $this->info('Dibatalkan.');
                return;
            }
        }

        $this->info('Memulai import...');

        if ($dryRun) {
            $this->warn('DRY RUN — data tidak disimpan ke DB');
        }

        Excel::import(new StudentDataImport, $file);

        $this->info('Import selesai.');
    }
}
