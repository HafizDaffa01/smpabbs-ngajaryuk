# Routes

All web routes defined in `routes/web.php`. Console commands in `routes/console.php`.

## Authentication (laravel/ui)

Auto-generated routes:

| Method | URI | Name |
|---|---|---|
| GET | `/login` | `login` |
| POST | `/login` | - |
| POST | `/logout` | `logout` |
| GET | `/register` | `register` |
| POST | `/register` | - |
| GET | `/password/reset` | `password.request` |
| POST | `/password/email` | `password.email` |
| GET | `/password/reset/{token}` | `password.reset` |
| POST | `/password/reset` | `password.update` |
| GET | `/password/confirm` | `password.confirm` |
| POST | `/password/confirm` | - |
| GET | `/email/verify` | `verification.notice` |
| GET | `/email/verify/{id}/{hash}` | `verification.verify` |
| POST | `/email/resend` | `verification.resend` |

---

## Root

| Method | URI | Middleware | Action |
|---|---|---|---|
| GET | `/` | - | Closure: redirects admin to `/admin`, teacher to `/absensi`, guest to `/login` |
| ANY | `/home` | - | Closure: redirects to `/` |

---

## Admin Routes

All require `auth` + `admin` middleware.

### Dashboard & Management

| Method | URI | Name | Action |
|---|---|---|---|
| GET | `/admin` | `admin.index` | AdminController@index |
| GET | `/admin/teacher/table` | `admin.teacher.table` | AdminController@teacherTable |

### Teacher CRUD

| Method | URI | Name | Action |
|---|---|---|---|
| POST | `/admin/import-teachers` | `import.teachers` | AdminController@importTeachers |
| POST | `/admin/add-teacher` | `admin.addTeacher` | TeacherController@store |
| PUT | `/admin/teacher/{id}` | `admin.teacher.update` | AdminController@updateTeacher |
| PUT | `/admin/teacher/{id}/mapel` | `admin.teacher.updateMapel` | AdminController@updateTeacherMapel |

### User Management

| Method | URI | Name | Action |
|---|---|---|---|
| DELETE | `/admin/user/{id}` | `admin.deleteUser` | AdminController@deleteUser |
| PUT | `/admin/user/{id}/make-admin` | `admin.makeAdmin` | AdminController@makeAdmin |
| PUT | `/admin/user/{id}/remove-admin` | `admin.removeAdmin` | AdminController@removeAdmin |

### Student Management

| Method | URI | Name | Action |
|---|---|---|---|
| POST | `/import-students` | `import.students` | ImportController@import |
| DELETE | `/admin/student/{id}` | `delete.student` | AdminController@deleteStudent |
| PUT | `/admin/student/{id}/update-grade` | `admin.student.updateGrade` | AdminController@updateGrade |

### Attendance Management

| Method | URI | Name | Action |
|---|---|---|---|
| DELETE | `/admin/absensi/{id}` | `admin.deleteAbsensi` | AdminController@deleteAbsensi |
| GET | `/admin/absensi/delete-all` | `admin.deleteAllAbsensi` | AdminController@deleteAllAbsensi |
| GET | `/admin/absensi/delete-by-period` | `admin.deleteAbsensiByPeriod` | AdminController@deleteAbsensiByPeriod |

### Journal (Admin)

| Method | URI | Name | Action |
|---|---|---|---|
| GET | `/admin/ts` | `jurnal.admin` | JournalController@admin |
| GET | `/journal/export` | `journal.export` | JournalController@export |

### PDF Export

| Method | URI | Name | Action |
|---|---|---|---|
| GET | `/export/pdf/rekap-presensi` | `rekap.pdf` | PdfController@generateRekapPresensi |
| GET | `/export/pdf/rekap-kbm` | `rekap.kbm.pdf` | PdfController@generateRekapKBM |

### Backup

| Method | URI | Name | Action |
|---|---|---|---|
| GET | `/backup` | `backup.index` | BackupController@index |
| POST | `/backup/save` | `backup.save` | BackupController@save |
| GET | `/backup/export-waktu` | `backup.exportWaktu` | BackupController@exportWaktu |
| GET | `/backup/export-lokasi` | `backup.exportLokasi` | BackupController@exportLokasi |
| GET | `/backup/export-zip` | `backup.exportGambar` | BackupController@exportGambar |

### Schedule

| Method | URI | Name | Action |
|---|---|---|---|
| GET | `/schedule` | `schedule.index` | ScheduleController@index |
| POST | `/schedule/import` | `schedule.import` | ScheduleController@import |

### File Explorer

| Method | URI | Name | Action |
|---|---|---|---|
| GET | `/explorer` | `explorer.index` | FileManagerController@index |
| POST | `/explorer/folder` | `explorer.folder` | FileManagerController@createFolder |
| POST | `/explorer/upload` | `explorer.upload` | FileManagerController@uploadFile |
| POST | `/explorer/rename` | `explorer.rename` | FileManagerController@rename |
| DELETE | `/explorer/delete-file` | `explorer.deleteFile` | FileManagerController@deleteFile |
| DELETE | `/explorer/delete-folder` | `explorer.deleteFolder` | FileManagerController@deleteFolder |

---

## Teacher Routes (Auth Required)

| Method | URI | Name | Action |
|---|---|---|---|
| GET | `/absensi` | `absensi.index` | AbsensiController@index |
| POST | `/absensi` | `absensi.store` | AbsensiController@store |
| GET | `/journal` | `journal.selectClass` | JournalController@selectClass |
| GET | `/journal/show` | `journal.show` | JournalController@showClass |
| POST | `/journal/save-all` | `journal.saveAll` | JournalController@saveAll |
| POST | `/journal/note` | `journal.saveNote` | JournalController@saveNote |
| GET | `/prevSmes` | `rekap.index` | JournalController@rekapIndex |
| GET | `/prevSmes/show` | `rekap.show` | JournalController@showRekap |
| GET | `/prevSmes/presensi` | `rekap.showPresensi` | JournalController@showRekapPresensi |
| GET | `/profile` | `profile.edit` | ProfileController@edit |
| PUT | `/profile` | `profile.update_new` | ProfileController@update |
| POST | `/profile/update` | `profile.update` | TeacherController@updateProfile |

---

## Utility Routes

| Method | URI | Name | Action |
|---|---|---|---|
| GET | `/success` | `success` | Closure - success view |
| GET | `/error` | `error` | Closure - error view |

---

## Console Commands

Defined in `routes/console.php`:

| Command | Description | Schedule |
|---|---|---|
| `send:whatsapp-alert` | Send WhatsApp reminders to teachers who haven't checked in | Daily at 08:00 |
| `run:build` | Full production build (migrate:fresh --seed, optimize, npm build) | Manual |
| `run:genstudents` | Generate random students for classes 7A-9F | Manual |
| `run:backup` | Backup database + uploads to password-protected ZIP | Manual |
| `run:clean` | Clear cache and old logs (>30 days) | Manual |
| `run:monitor` | Server health check (DB, storage, queue, PHP, disk) | Manual |
| `inspire` | Display inspiring quote | - |

---

## Middleware Groups

```
web:     EncryptCookies, AddQueuedCookiesToResponse, StartSession, ShareErrors, VerifyCsrfToken, SubstituteBindings
auth:    Authenticate
admin:   Authenticate + AdminMiddleware (is_admin check)
```

Global: `ForceHttpsFromProxy`, `LogSlowRequests`
