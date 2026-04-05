<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use App\Models\Student;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schedule;

Schedule::command('send:whatsapp-alert')->dailyAt('08:00');

// ======================
// INSPIRE COMMAND
// ======================
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ======================
// BUILD COMMAND
// ======================
Artisan::command('run:build', function () {

    if (!app()->environment('production')) {
        if (!$this->confirm("Ini BUKAN production. Database akan dihapus. Lanjut?")) {
            $this->warn("Build dibatalkan.");
            return;
        }
    }

    $this->info('Starting build process...');

    // ======================
    // Database reset
    // ======================
    $this->comment('Reset database...');
    $this->call('migrate:fresh', [
        '--force' => true,
        '--seed'  => true
    ]);

    // ======================
    // Laravel optimization
    // ======================
    $this->comment('Optimizing Laravel...');
    $this->call('optimize:clear');
    $this->call('config:clear');
    $this->call('route:clear');
    $this->call('view:clear');
    $this->call('cache:clear');

    $this->call('config:cache');
    $this->call('route:cache');
    $this->call('view:cache');
    $this->call('optimize');

    // ======================
    // NPM build (frontend)
    // ======================
    if (file_exists(base_path('package.json'))) {
        $this->comment('Building frontend (npm)...');

        $process = Process::run('npm run build');

        if ($process->failed()) {
            $this->error('NPM build gagal');
            $this->line($process->errorOutput());
            return;
        }

        $this->info('NPM build selesai');
    } else {
        $this->warn('package.json tidak ditemukan, skip npm build');
    }

    // ======================
    // Key (optional)
    // ======================
    if (empty(config('app.key'))) {
        $this->call('key:generate', ['--force' => true]);
    }

    // ======================
    // Info
    // ======================
    $this->info('Build completed successfully!');
    $this->call('migrate:status');

})->purpose('Run full production build & optimization');

// ======================
// GENERATE STUDENTS (with input)
// ======================
Artisan::command('run:genstudents', function () {

    // INPUT jumlah murid per kelas
    $jumlah = $this->ask("Berapa student yang ingin di-generate per kelas?", 2);

    // Validasi angka
    if (!is_numeric($jumlah) || $jumlah < 1) {
        $this->error("Input tidak valid. Masukkan angka minimal 1.");
        return;
    }

    $jumlah = (int)$jumlah;

    $this->info("Generating $jumlah students untuk setiap kelas 7A sampai 9F...");

    $grades = [];

    // Generate kelas 7A–9F
    foreach ([7, 8, 9] as $kelas) {
        foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $sub) {
            $grades[] = $kelas . $sub;
        }
    }

    $proguls = ['ICT', 'TCP'];

    foreach ($grades as $grade) {

        $this->info("=== Kelas $grade ===");

        for ($i = 1; $i <= $jumlah; $i++) {

            $name   = "Student " . Str::upper(Str::random(5));
            $proglu = $proguls[array_rand($proguls)];

            $this->info("✔ $name @ $grade | $proglu");

            Student::create([
                'name'   => $name,
                'grade'  => $grade,
                'progul' => $proglu,
            ]);
        }
    }

    $this->info("✔ Selesai! $jumlah murid per kelas berhasil dibuat.");
})->purpose('Generate random students for each class 7A to 9F');

Artisan::command('run:backup', function () {

    $this->comment("Menjalankan backup database dan file...");

    $backupPath = storage_path('backups');
    if (!is_dir($backupPath)) mkdir($backupPath, 0777, true);

    $timestamp = date('Y-m-d_H-i-s');
    $sqlFile   = $backupPath . DIRECTORY_SEPARATOR . "backup_$timestamp.sql";
    $zipFile   = $backupPath . DIRECTORY_SEPARATOR . "backup_$timestamp.zip";
    $password  = "smpABBS_2025admin";

    // ---- Backup database ----
    $db = config('database.connections.mysql');

    // wrap path in quotes
    $cmd = '"' . "mysqldump" . '"' .
        " -u{$db['username']} -p{$db['password']} {$db['database']} > " .
        '"' . $sqlFile . '"';

    system($cmd);

    if (!file_exists($sqlFile) || filesize($sqlFile) < 10) {
        $this->error("Gagal membuat SQL backup. Cek mysqldump PATH / credentials.");
        return;
    }

    // ---- ZIP + Password ----
    $zip = new \ZipArchive();
    if ($zip->open($zipFile, ZipArchive::CREATE) === TRUE) {

        $zip->setPassword($password);

        // masukkan database.sql
        $zip->addFile($sqlFile, "database.sql");
        $zip->setEncryptionName("database.sql", ZipArchive::EM_AES_256);

        // masukkan folder uploads
        $uploads = public_path('uploads');
        if (is_dir($uploads)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($uploads, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($files as $file) {
                if ($file->isFile()) {
                    $localName = 'uploads/' . str_replace($uploads . '/', '', $file);
                    $zip->addFile($file, $localName);
                    $zip->setEncryptionName($localName, ZipArchive::EM_AES_256);
                }
            }
        }

        $zip->close();
    } else {
        $this->error("Gagal membuat ZIP backup.");
        return;
    }

    unlink($sqlFile);

    $this->info("✔ Backup selesai! File: $zipFile");
    $this->info("   Password: $password");
})
->purpose('Backup database and uploads folder into a password-protected ZIP file');


Artisan::command('run:clean', function () {

    $this->comment("Membersihkan cache & log...");

    $this->call('cache:clear');
    $this->call('config:clear');
    $this->call('route:clear');
    $this->call('view:clear');
    $this->call('optimize:clear');

    // Hapus log > 30 hari
    $logPath = storage_path('logs');
    $files = glob("$logPath/*.log");

    foreach ($files as $f) {
        if (filemtime($f) < strtotime('-30 days')) {
            unlink($f);
        }
    }

    $this->info("✔ Clean selesai!");
})
->purpose('Clean cache and old log files');

Artisan::command('run:monitor', function () {

    $this->comment("=== Laravel Server Monitor ===\n");

    // --- 1. Database check ---
    $this->comment("[1️] Database Connection:");
    try {
        DB::connection()->getPdo();
        $this->info("✔ Database: OK");
    } catch (\Exception $e) {
        $this->error("✖ Database ERROR: " . $e->getMessage());
    }

    // --- 2. Storage writable ---
    $this->comment("\n[2️] Storage Permissions:");
    $storageWritable = is_writable(storage_path());
    $this->info("Storage folder writable: " . ($storageWritable ? "✔ Yes" : "✖ No"));
    $this->info("Storage path: " . storage_path());

    // --- 3. Storage link ---
    $this->comment("\n[3️] Storage Link:");
    $storageLink = public_path('storage');
    if (is_link($storageLink)) {
        $this->info("✔ Storage link exists (public/storage → storage/app/public)");
    } else {
        $this->error("✖ Storage link not found! Run: php artisan storage:link");
    }

    // --- 4. Queue Worker ---
    $this->comment("\n[4] Queue Worker Status:");
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $queueStatus = shell_exec('tasklist /FI "IMAGENAME eq php.exe" /V | findstr queue:work');
    } else {
        $queueStatus = shell_exec("pgrep -f 'queue:work'");
    }
    $this->info("Queue Worker: " . ($queueStatus ? "✔ Running" : "✖ Not running"));
    $this->info("Command running: php artisan queue:work");

    // --- 5. Cache Driver ---
    $this->comment("\n[5] Cache Driver:");
    $cacheDriver = config('cache.default');
    $this->info("Current cache driver: $cacheDriver");

    // --- 6. Route count ---
    $this->comment("\n[6] Routes:");
    $routes = collect(\Route::getRoutes())->count();
    $this->info("Total routes registered: $routes");

    // --- 7. PHP info ---
    $this->comment("\n[7] PHP & Extensions:");
    $this->info("PHP Version: " . phpversion());
    $this->info("Loaded extensions: " . implode(', ', get_loaded_extensions()));

    // --- 8. Disk space ---
    $this->comment("\n[8] Disk Space:");
    $rootDrive = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? getenv('SystemDrive') . '/' : '/';
    $free  = round(disk_free_space($rootDrive) / 1024 / 1024 / 1024, 2);
    $total = round(disk_total_space($rootDrive) / 1024 / 1024 / 1024, 2);
    $this->info("Disk ($rootDrive) free: $free GB / total: $total GB");

    // --- 9. Environment ---
    $this->comment("\n[9] Laravel Environment:");
    $this->info("APP_ENV: " . config('app.env'));
    $this->info("APP_DEBUG: " . (config('app.debug') ? '✔ True' : '✖ False'));

    // --- 10. Session driver ---
    $this->comment("\n[10] Session Driver:");
    $sessionDriver = config('session.driver');
    $this->info("Current session driver: $sessionDriver");

    // --- 11. Mail driver ---
    $this->comment("\n[11] Mail Driver:");
    $mailDriver = config('mail.default');
    $this->info("Current mail driver: $mailDriver");

    // --- 12. Queue connection ---
    $this->comment("\n[12] Queue Connection:");
    $queueConn = config('queue.default');
    $this->info("Default queue connection: $queueConn");

    $this->comment("\n=== Monitor Completed ===");

})->purpose('Monitor application health and status.');
