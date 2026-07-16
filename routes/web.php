<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\JournalController;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\FileManagerController;
use App\Http\Controllers\ProfileController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Root → cek login & role
Route::get('/', function () {
    if (!Auth::check()) {
        return redirect('/login'); // belum login → ke login
    }
    return Auth::user()->is_admin ? redirect('/admin') : redirect('/absensi');
});

// Auth routes (login/register) with rate limiting
Route::middleware(['throttle:auth'])->group(function () {
    Auth::routes([
        'reset' => false,
        'verify' => false,
    ]);
});

// Admin routes
Route::middleware(['auth', 'admin'])->group(function () {
    
    // Admin Core
    Route::prefix('admin')->group(function () {
        Route::controller(AdminController::class)->group(function () {
            Route::get('/', 'index')->name('admin.index');
            Route::delete('/user/{id}', 'deleteUser')->name('admin.deleteUser');
            Route::delete('/absensi/{id}', 'deleteAbsensi')->name('admin.deleteAbsensi');
            Route::post('/absensi/delete-all', 'deleteAllAbsensi')->name('admin.deleteAllAbsensi');
            Route::post('/absensi/delete-by-period', 'deleteAbsensiByPeriod')->name('admin.deleteAbsensiByPeriod');
            Route::delete('/student/{id}', 'deleteStudent')->name('delete.student');
            Route::put('/student/{id}/update-grade', 'updateGrade')->name('admin.student.updateGrade');
            Route::put('/teacher/{id}/mapel', 'updateTeacherMapel')->name('admin.teacher.updateMapel');
            Route::put('/teacher/{id}', 'updateTeacher')->name('admin.teacher.update');
            Route::put('/user/{id}/make-admin', 'makeAdmin')->name('admin.makeAdmin');
            Route::put('/user/{id}/remove-admin', 'removeAdmin')->name('admin.removeAdmin');
            Route::get('/teacher/table', 'teacherTable')->name('admin.teacher.table');
            Route::post('/import-teachers', 'importTeachers')->name('import.teachers');
        });

        Route::get('/ts', [JournalController::class, 'admin'])->name('jurnal.admin');
        Route::post('/add-teacher', [TeacherController::class, 'store'])->name('admin.addTeacher');
    });

    // Student Import
    Route::post('/import-students', [ImportController::class, 'import'])->name('import.students');

    // Journal Exports
    Route::get('/journal/export', [JournalController::class, 'export'])->name('journal.export');

    // PDF Exports
    Route::prefix('export/pdf')->controller(PdfController::class)->group(function () {
        Route::get('/rekap-presensi', 'generateRekapPresensi')->name('rekap.pdf');
        Route::get('/rekap-kbm', 'generateRekapKBM')->name('rekap.kbm.pdf');
    });

    // Backup
    Route::prefix('backup')->controller(BackupController::class)->group(function () {
        Route::get('/', 'index')->name('backup.index');
        Route::post('/save', 'save')->name('backup.save');
        Route::get('/export-zip', 'exportGambar')->name('backup.exportGambar');
        Route::get('/export-waktu', 'exportWaktu')->name('backup.exportWaktu');
        Route::get('/export-lokasi', 'exportLokasi')->name('backup.exportLokasi');
    });

    // Schedule Management
    Route::prefix('schedule')->controller(ScheduleController::class)->group(function () {
        Route::get('/', 'index')->name('schedule.index');
        Route::post('/import', 'import')->name('schedule.import');
    });

    // Explorer (File Manager)
    Route::prefix('explorer')->controller(FileManagerController::class)->group(function () {
        Route::get('/', 'index')->name('explorer.index');
        Route::post('/folder', 'createFolder')->name('explorer.folder');
        Route::post('/upload', 'uploadFile')->name('explorer.upload');
        Route::post('/rename', 'rename')->name('explorer.rename');
        Route::delete('/delete-file', 'deleteFile')->name('explorer.deleteFile');
        Route::delete('/delete-folder', 'deleteFolder')->name('explorer.deleteFolder');
    });
});

// User routes (harus login)
Route::middleware(['auth'])->group(function () {
    // Absensi
    Route::prefix('absensi')->controller(AbsensiController::class)->group(function () {
        Route::get('/', 'index')->name('absensi.index');
        Route::post('/', 'store')->name('absensi.store');
    });

    // Journal
    Route::prefix('journal')->controller(JournalController::class)->group(function () {
        Route::get('/', 'selectClass')->name('journal.selectClass');
        Route::get('/show', 'showClass')->name('journal.show');
        Route::post('/save-all', 'saveAll')->name('journal.saveAll');
        Route::post('/note', 'saveNote')->name('journal.saveNote');
    });

    // Rekap Semester & Presensi
    Route::prefix('prevSmes')->controller(JournalController::class)->group(function () {
        Route::get('/', 'rekapIndex')->name('rekap.index');
        Route::get('/show', 'showRekap')->name('rekap.show');
        Route::get('/presensi', 'showRekapPresensi')->name('rekap.showPresensi');
    });

    // Profile Update (New Page)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update_new');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    // Profile Update (Legacy API for Modal)
    Route::post('/profile/update', [TeacherController::class, 'updateProfile'])->name('profile.update');
});

// Utility Routes
Route::get('/success', function () {
    return view('success');
})->name('success');

Route::get('/error', function () {
    return view('error');
})->name('error');

// Redirect /home ke /
Route::redirect('/home', '/');
