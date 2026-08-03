# Product Requirements Document (PRD)
# Jurnal Kelas - SMP ABBS Surakarta School Management System

---

## 1. Executive Summary

**Project Name:** Jurnal Kelas  
**School:** SMP ABBS Surakarta  
**Type:** Laravel-based School Management System  
**Primary Users:** Teachers (Guru), Administrators (Admin), Students (indirect via attendance)  
**Core Purpose:** Digitalize teacher attendance, student attendance tracking, teaching journal (KBM), schedule management, and administrative reporting for a junior high school.

---

## 2. System Architecture Overview

### 2.1 Tech Stack
| Layer | Technology | Version |
|-------|------------|---------|
| **Backend** | Laravel | ^12.0 |
| **Frontend** | Blade Templates + Bootstrap 5.2.3 + Tailwind CSS 4.x | - |
| **Build Tool** | Vite | ^7.0.4 |
| **Database** | SQLite (dev) / MySQL/PostgreSQL (prod) | - |
| **Excel/CSV** | Maatwebsite Excel | ^3.1 |
| **PDF Generation** | barcoding/Queue** | Database queue driver | - |
| **Authentication** | Laravel Breeze/UI | ^4.6 |
| **External APIs** | Fonnte (WhatsApp), OpenStreetMap Nominatim (Geocoding), Leaflet.js (Maps) | - |

### 2.2 Key Architectural Decisions
1. **Single Table for Users/Teachers**: `users` table serves both admins and teachers via `is_admin` boolean flag. `Teacher` model is a proxy over `users` table.
2. **No Event/Job System**: All processing is synchronous (WhatsApp sending, imports, exports).
3. **Always Dark Mode**: Hardcoded dark theme via CSS custom properties.
4. **Period System**: 21st-to-20th monthly cycles (e.g., "Januari 21 - Februari 20") for payroll/attendance reporting.
5. **Subject Normalization**: 50+ Indonesian/English variants mapped to 12 canonical subject names.

---

## 3. User Roles & Permissions

### 3.1 Role Matrix
| Feature | Admin (`is_admin=1`) | Teacher (`is_admin=0`) |
|---------|---------------------|------------------------|
| Dashboard Statistics | ✅ Full | ❌ |
| Teacher CRUD (TS Manager) | ✅ | ❌ |
| Student CRUD (Import/Manual) | ✅ | ❌ |
| Schedule Management | ✅ | ❌ |
| Journal Export (Excel) | ✅ | ❌ |
| PDF Reports (Rekap) | ✅ | ✅ View only |
| Backup/Export Attendance | ✅ | ❌ |
| File Explorer | ✅ | ❌ |
| Teacher Check-in (Absensi) | ✅ | ✅ |
| Student Attendance Input | ❌ | ✅ |
| KBM/Journal Entry | ❌ | ✅ |
| Recap View (Semester/Daily) | ✅ | ✅ |
| Profile Edit | ✅ | ✅ |

### 3.2 Middleware
- **`admin`** (`AdminMiddleware`): Checks `auth` + `is_admin=1`
- **`auth`**: Standard Laravel authentication
- **`forcehttps`** (`ForceHttpsFromProxy`): Global, handles Cloudflare Tunnel `X-Forwarded-Proto`
- **`throttle:auth`**: Rate limiting on auth routes

---

## 4. Core Modules & Features

### 4.1 Module 1: Teacher Attendance (Absensi Guru) 🎯 **Core Feature**

#### 4.1.1 User Flow
```
Teacher → /absensi → GPS Permission → Camera Capture → 
Geofence Validation (1km radius SMP ABBS) → 
Duplicate Check (same day) → Save to DB → 
WhatsApp Notification (Fonnte) → Success Page
```

#### 4.1.2 Technical Specifications

**Database: `absensis` table**
| Column | Type | Constraints |
|--------|------|-------------|
| `id` | bigint | PK, AI |
| `user_id` | bigint | FK → users.id, nullable |
| `nama` | string | Denormalized teacher name |
| `unit` | string | Default: "SMP ABBS Surakarta" |
| `lokasi` | string | GPS coordinates "lat,lon" |
| `alamat` | text | Reverse geocoded address (nullable) |
| `foto` | longText | Base64 or file path "uploads/..." |
| `akurasi` | string | GPS accuracy in meters (nullable) |
| `waktu` | timestamp | Check-in time |
| `created_at`/`updated_at` | timestamp | |

**Validation Rules** (`AbsensiController::store`)
```php
'lokasi' => 'required|string',
'foto' => 'required|string',        // base64
'alamat' => 'nullable|string',
'lat' => 'nullable|numeric|between:-90,90',
'lon' => 'nullable|numeric|between:-180,180',
```

**Geofence Logic** (`AbsensiController:45-55`)
- School coordinates: `-7.5564, 110.8347` (SMP ABBS Surakarta)
- Max radius: **1000 meters**
- Uses Haversine formula (`haversineDistance` method)
- Server-side validation even if client sends GPS

**Duplicate Prevention** (`AbsensiController:58-65`)
- Atomic check: `where('user_id', $user->id)->whereDate('waktu', today())->first()`
- Returns error with existing check-in time if found

**Photo Processing** (`AbsensiController:67-101`)
- Base64 decode with MIME validation
- Max 5MB
- Allowed: JPEG, PNG, GIF, WebP
- Saved to `public/uploads/{sanitized_name}@{timestamp}.png`

**WhatsApp Notification** (`AbsensiController:114-202`)
- Via **Fonnte API** (`config/fonnte.php`)
- Message template includes:
  - Teacher name
  - Check-in time
  - Today's teaching schedule (from `schedules` table)
  - Journal link: `gurusmpabbs.alabidin.sch.id/journal`
- Sent asynchronously (fire-and-forget with logging)

#### 4.1.3 Frontend: `resources/views/absensi/index.blade.php`
- Leaflet.js map showing current location
- Camera capture via `navigator.mediaDevices.getUserMedia`
- GPS via `navigator.geolocation.getCurrentPosition`
- SweetAlert2 for confirmations
- Bottom navigation for mobile

---

### 4.2 Module 2: Student Attendance & Journal (Jurnal Kelas / KBM) 🎯 **Core Feature**

#### 4.2.1 Class Selection
- Route: `/journal` → `JournalController::selectClass()`
- Groups classes by grade (7, 8, 9, 7A-7F, etc.) from `schedules` table
- View: `journal/select-class.blade.php`

#### 4.2.2 Class Detail View (`/journal/show`) - **Most Complex Page**
**Controller**: `JournalController::showClass()` (lines 150-410)

**Data Aggregation:**
1. **Students**: Filter by grade/class from `students` table
2. **Teachers & Subjects**: From `schedules` table filtered by:
   - Class name (exact match or grade-level for Leadership)
   - Current day of week
   - Leadership classes (7,8,9) get special handling: all Leadership subjects across sections
3. **Attendance**: From `attendances` table for selected month/year
4. **KBM Notes**: From `notes` table for selected date + class
5. **Summary**: S/I/A counts per student

**Special Logic - Leadership Classes (Grades 7,8,9 without section)**
- Fetches ALL schedules where `class_name LIKE '7%'` AND subject = "Leadership"
- Merges teachers from both `users.mapel` (JSON) and `schedules` table
- Deduplicates by teacher name (case-insensitive)

**View**: `resources/views/journal/class-detail.blade.php` (~1400 lines)
- **Collapsible Sections**: Teachers/Subjects, Attendance
- **Inline Editable Attendance Grid**: 31 days × students
  - Click cell → dropdown (S/I/A/-) → auto-save on change
  - Today column highlighted (blue)
  - Selected date column highlighted (blue gradient)
  - Search filter for student names
  - Sticky columns (No, Name) on mobile
- **KBM Editor**: Click subject row → modal/input → saves to `notes` table
- **Save All Button**: Batches attendance + KBM → `JournalController::saveAll()`
- **Date Navigation**: Prev/Next day, Flatpickr calendar
- **Export Excel** (admin only): `JournalController::export()`

#### 4.2.3 Attendance Data Model (`attendances` table)
| Column | Type | Notes |
|--------|------|-------|
| `student_id` | bigint | FK → students.id (cascade) |
| `day` | tinyInteger | 1-31 |
| `month` | tinyInteger | 1-12 |
| `year` | smallInteger | e.g., 2025 |
| `value` | char(1) | 'S' (Sakit), 'I' (Izin), 'A' (Alpha/Alpa) |
| Composite Unique | (student_id, day, month, year) | Via updateOrCreate |

#### 4.2.4 KBM Notes Model (`notes` table)
| Column | Type | Notes |
|--------|------|-------|
| `class` | string(10) | e.g., "7A" |
| `subject` | string(50) | Canonical subject name |
| `teacher_id` | bigint | FK → users.id (nullable, set null) |
| `date` | date | Lesson date |
| `time` | string(5) | "HH:MM" format |
| `note` | text | Teaching notes |
| `checked` | boolean | Default false |

#### 4.2.5 Save All API (`JournalController::saveAll()`)
```php
// Attendance batch
foreach ($request->attendance as $r) {
    Attendance::updateOrCreate(
        ['student_id'=>$r['student_id'], 'day'=>$r['day'], 'month'=>$request->month, 'year'=>$request->year],
        ['value'=>$r['value']]
    );
}
// KBM batch
foreach ($request->kbm as $k) {
    Note::updateOrCreate(
        ['class'=>$class, 'subject'=>$k['subject'], 'date'=>$k['date']],
        ['teacher_id'=>$k['teacher_id'], 'time'=>$k['time'], 'note'=>$k['note'], 'checked'=>true]
    );
}
```

---

### 4.3 Module 3: Recap & Reporting (Rekap) 📊

#### 4.3.1 Semester Recap (`/prevSmes`) - `JournalController::rekapIndex()` + `showRekap()`
- **Views**: `rekap-select-class.blade.php` → `rekap-semester.blade.php`
- **Modes**: Semester (6 months) or Daily
- **Data**: KBM notes grouped by date + Attendance grouped by date
- **Schedule Context**: Shows scheduled subjects per day for reference

#### 4.3.2 Attendance Recap (`/prevSmes/presensi`) - `JournalController::showRekapPresensi()`
- **Views**: `rekap-presensi.blade.php`
- **Modes**: Semester (all months) or Monthly
- **Output**: Grid of students × days with S/I/A + Summary totals (S/I/A counts)
- **PDF Export**: `PdfController::generateRekapPresensi()` → `pdf/rekap-presensi.blade.php` (landscape A4)

#### 4.3.3 KBM Recap PDF (`PdfController::generateRekapKBM()`)
- Template: `pdf/rekap-kbm.blade.php` (landscape A4)
- Includes: Schedule, attendance summary, KBM notes per date

---

### 4.4 Module 4: Schedule Management (Jadwal) 📅

#### 4.4.1 Schedule Model (`schedules` table)
| Column | Type | Notes |
|--------|------|-------|
| `class_name` | string | "7A", "8B", etc. |
| `day` | string | English: "Monday"..."Saturday" |
| `period` | integer | 1, 2, 3... (ordering) |
| `subject` | string | **Normalized** canonical name |
| `subject_display` | string | Original uppercase display name |
| `teacher` | string | Teacher name (uppercase, nullable) |
| `start_time`/`end_time` | time | Nullable |

#### 4.4.2 Auto-Teacher Assignment (Model Boot)
- On `Schedule::saving`, if `teacher` empty but `subject` exists:
  - Searches `Teacher` (users with `is_admin=0`) where `mapel` JSON matches subject+class
  - Supports multiple JSON formats (dict by class, list of objects, flat array)
  - Sets `teacher = strtoupper($teacher->name)`

#### 4.4.3 Import from Excel (`ScheduleController::import()`)
- **File Format**: Multi-sheet Excel
  - Row 1: Class names per column block
  - Row 2: Day names (Indonesian: "Senin", "Selasa"...)
  - Row 3+: Periods with subjects (can contain "Subject - Teacher")
- **Processing**:
  - Deletes ALL existing schedules first (`Schedule::query()->delete()`)
  - Parses each "Senin" column block → 6 days (Mon-Sat)
  - Subject normalization via `getMapelMapping()` (same as TeacherImport)
  - Splits "Subject - Teacher" format
  - Creates/updates by `(class_name, day, period)`
- **Subject Mapping**: 95+ variants → 12 canonical names

---

### 4.5 Module 5: Teacher Management (TS Manager) 👨‍🏫

#### 4.5.1 Admin Dashboard (`/admin`) - `AdminController::index()`
- Stats cards: Total Teachers, Students, Classes, Attendance Records
- Current period indicator (21st-20th cycle)
- Student list grouped by class

#### 4.5.2 Teacher CRUD (`/admin/tsmanager`) - `AdminController::teacherTable()` + AJAX
- **View**: `admin/tsmanager.blade.php` + partial `admin/partials/teacher-table.blade.php`
- **Features**:
  - List all teachers (non-admin users)
  - Inline edit: Name, Email, Phone, Password
  - Subject (Mapel) Management: JSON editor with class-subject mapping
  - Role toggle: Make Admin / Remove Admin
  - Delete teacher (cascades to attendance records)

#### 4.5.3 Teacher Import (`AdminController::importTeachers()`)
- Uses `TeacherImport` class (implements `ToCollection`, `WithHeadingRow`, `WithEvents`)
- **Two Excel Formats Supported**:
  - **Format A**: Columns = Subjects, Rows = Teachers, Cells = Classes (e.g., "7A 7B")
  - **Format B**: Columns = Classes (7A, 8B...), Rows = Teachers, Cells = Subjects
- **Auto-detection**: `detectFormat()` analyzes headers + first 10 rows
- **Subject Normalization**: `mapSubject()` using 97-entry mapping dictionary
- **Class Parsing**: Handles ranges "A-F", multi-grade "7 ABC", "8A 8B"
- **Deduplication**: `flattenClasses()` prevents duplicate class-subject pairs
- **Cleanup**: `AfterImport` event deletes teachers not in current import (soft sync)

#### 4.5.4 Teacher Model JSON Handling (`Teacher` model)
- **Accessor** (`getMapelAttribute`): Fixes malformed JSON → returns array
- **Mutator** (`setMapelAttribute`): Normalizes subject names recursively, stores as valid JSON
- **Formats Supported**:
  - Dict by class: `{"7A": ["ICT"], "7": ["Math"]}`
  - List of objects: `[{"kelas":"7A","mapel":"ICT"}]`
  - Flat array: `["ICT", "Math"]` (global teacher)

---

### 4.6 Module 6: Student Management 👨‍🎓

#### 4.6.1 Import Students (`ImportController::import()`)
- **Multi-sheet**: Processes first 3 sheets via `StudentsMultiSheetImport`
- **Columns**: Col 1 = Name, Col 2 = Class (grade)
- **Deduplication**: In-memory cache + `unique(name, grade)` constraint
- **Batch Insert**: Single `Student::insert()` per sheet

#### 4.6.2 Manual Add
- POST `/import-students` with `name` + `grade` JSON

#### 4.6.3 Grade Update (Admin)
- PUT `/admin/student/{id}/update-grade`

---

### 4.7 Module 7: Backup & Export System 💾

#### 4.7.1 Backup Index (`/backup`) - `BackupController::index()`
- **Filter**: Period (21st-20th), Year, Data Type (Time/Location/Image), Search
- **Grid View**: Teachers × Days matrix
- **Data Types**:
  - **Waktu**: Check-in times (HH:MM)
  - **Lokasi**: GPS coordinates
  - **Gambar**: Photo references

#### 4.7.2 Inline Grid Editing (`BackupController::save()`)
- AJAX POST to update individual cells
- Creates missing attendance records with placeholder data

#### 4.7.3 Exports
| Export | Route | Format | Description |
|--------|-------|--------|-------------|
| Waktu | `/backup/export-waktu` | CSV | Time grid for selected period |
| Lokasi | `/backup/export-lokasi` | CSV | GPS + address for year |
| Gambar | `/backup/export-gambar` | ZIP | All attendance photos renamed |

#### 4.7.4 Bulk Delete
- `/admin/absensi/delete-all` → `AdminController::deleteAllAbsensi()`
  - Optional: delete physical photo files
- `/admin/absensi/delete-by-period` → `AdminController::deleteAbsensiByPeriod()`
  - By month/year, optional photo deletion

---

### 4.8 Module 8: File Explorer 📁

#### 4.8.1 Features (`FileManagerController`)
- Browse `public/uploads/` directory tree
- Create folders (alphanumeric + `_-.`)
- Upload files (10MB max, images/docs/archives)
- Rename files/folders
- Delete files/folders (recursive)
- **Security**: `resolvePath()` prevents directory traversal via `realpath()` + prefix check

#### 4.8.2 View: `explorer.blade.php`
- Tree navigation + file grid
- Modal-based operations

---

### 4.9 Module 9: PDF Generation 📄

#### 4.9.1 Controllers
- `PdfController::generateRekapPresensi()` - Attendance recap (landscape A4)
- `PdfController::generateRekapKBM()` - KBM recap (landscape A4)

#### 4.9.2 Templates
- `resources/views/pdf/rekap-presensi.blade.php`
- `resources/views/pdf/rekap-kbm.blade.php`
- Uses `barryvdh/laravel-dompdf` (`Pdf` facade)

#### 4.9.3 Data Preparation
- Filters by class, semester (1=Jan-Jun, 2=Jul-Dec), year
- Monthly or semester view
- Aggregates attendance + KBM + schedules

---

### 4.10 Module 10: WhatsApp Notifications 📱

#### 4.10.1 Scheduled Command
- `routes/console.php`: `Schedule::command('send:whatsapp-alert')->dailyAt('08:00')`
- Command: `app/Console/Commands/SendWhatsappAlert.php` (not shown but referenced)

#### 4.10.2 Configuration (`config/fonnte.php`)
```php
'api_key' => env('FONNTE_API_KEY'),
'device_id' => env('FONNTE_DEVICE_ID'),
'api_url' => 'https://api.fonnte.com/send',
'country_code' => '62',
```

#### 4.10.3 Message Format (AbsensiController:151-160)
```
[NgajarYuk]
Halo *{Teacher Name}*, terima kasih sudah melakukan Presensi pada jam *{HH:MM}* ✅

Berikut jadwal mengajar Anda hari ini (*{Hari}*):
- Jam 1: Subject (Class)
- Jam 2: Subject (Class)
...

📌 Jangan lupa untuk mengisi jurnal harian setelah kegiatan mengajar.
🔗 *Isi Jurnal:* gurusmpabbs.alabidin.sch.id/journal
Tetap semangat mengajar! 💪
Tanggal: {DD-MM-YYYY}
Waktu : *{HH:MM}*
```

---

## 5. Database Schema Summary

### 5.1 Core Tables
```sql
users (id, name, email, phone_num, password, is_admin, mapel[JSON], email_verified_at, remember_token, timestamps)
absensis (id, user_id, nama, unit, lokasi, alamat, foto, akurasi, waktu, timestamps)
students (id, name, progul, grade, timestamps) -- unique(name, grade)
attendances (id, student_id, day, month, year, value[S/I/A], timestamps) -- FK cascade
notes (id, class, subject, teacher_id[FK->users], date, time, note, checked, timestamps)
schedules (id, class_name, day, period, subject, subject_display, teacher, start_time, end_time, timestamps)
password_reset_tokens, sessions, cache, jobs, failed_jobs (Laravel defaults)
```

### 5.2 Key Indexes
- `students.grade` (index)
- `students.unique(name, grade)`
- `attendances.student_id` (FK)
- `notes.teacher_id` (FK set null)
- `schedules.class_name, day` (compound for queries)

---

## 6. Subject Normalization Dictionary (Canonical → Variants)

| Canonical | Variants (Case-Insensitive) |
|-----------|----------------------------|
| **ICT** | ICT, TIK, Komputer, Informatika, Computer, IT |
| **SPORT** | SPORT, PJOK, Penjas, Olahraga, PHE, OLGA |
| **Civics** | CIVICS, PKN, PPKN, PPKn, PKn, Pendidikan Pancasila |
| **IFE** | IFE, Agama, Islam, PAI, BP, Pendidikan Agama Islam |
| **Indonesian** | INDONESIAN, BINDO, INDO, INDONESIA, B. INDO, B. INDONESIA, BAHASA INDONESIA |
| **Science** | SCIENCE, IPA, IPA, Ilmu Pengetahuan Alam |
| **Social** | SOCIAL, IPS, Ilmu Pengetahuan Sosial, Sejarah, Geografi, Ekonomi |
| **TKA INDO** | TKA INDO, TKAINDO, TI, TKAIND, TKA IND |
| **TKA Mathematics** | TKA MATH, TKAMATH, TKAMAT, TM, TKA MATHEMATICS, TKA MTK |
| **Quran** | QURAN, QUR'AN, AL-QURAN, AQ, Tahfidz |
| **English** | ENGLISH, INGGRIS, B. INGGRIS, BAHASA INGGRIS, ENG |
| **Mathematics** | MATHEMATICS, MATEMATIKA, MTK, MATH, MAT |
| **Leadership** | LEADERSHIP, Pramuka |

*Defined in `app/Helpers/SubjectHelper.php` + duplicated in `TeacherImport` + `ScheduleController`*

---

## 7. Period System (21st-20th Cycle)

### 7.1 Period Definitions (Hardcoded in `AdminController` + `BackupController`)
| Key | Label | Start | End |
|-----|-------|-------|-----|
| jan_feb | Januari 21 - Februari 20 | Jan 21 | Feb 20 |
| feb_mar | Februari 21 - Maret 20 | Feb 21 | Mar 20 |
| mar_apr | Maret 21 - April 20 | Mar 21 | Apr 20 |
| apr_mei | April 21 - Mei 20 | Apr 21 | May 20 |
| mei_jun | Mei 21 - Juni 20 | May 21 | Jun 20 |
| jun_jul | Juni 21 - Juli 20 | Jun 21 | Jul 20 |
| jul_agu | Juli 21 - Agustus 20 | Jul 21 | Aug 20 |
| agu_sep | Agustus 21 - September 20 | Aug 21 | Sep 20 |
| sep_okt | September 21 - Oktober 20 | Sep 21 | Oct 20 |
| okt_nov | Oktober 21 - November 20 | Oct 21 | Nov 20 |
| nov_des | November 21 - Desember 20 | Nov 21 | Dec 20 |
| des_jan | Desember 21 - Januari 20 | Dec 21 | Jan 20 (next year) |

### 7.2 Current Period Detection (`AdminController:36-64`)
- Iterates periods, checks if `now()` between start/end
- Used for default filter in admin dashboard & backup

### 7.3 Date Range Calculation (`BackupController::getPeriodRange()`)
- Handles year crossover (Des 21 - Jan 20)
- Returns Carbon `startOfDay()` / `endOfDay()` objects

---

## 8. Frontend Architecture

### 8.1 Layouts
| Layout | File | Usage |
|--------|------|-------|
| Main App | `layouts/app.blade.php` | All authenticated pages |
| Auth | `layouts/auth.blade.php` | Login, Register, Password Reset |
| CDN Partial | `layouts/cdn.blade.php` | Included in both above |

### 8.2 Main Layout Features (`layouts/app.blade.php`)
- **Dark Mode**: CSS custom properties (slate-900 bg, slate-800 cards, blue-500 primary)
- **Navbar**: Role-based links (Admin vs Teacher)
- **Mobile Bottom Nav**: 5 icons (Absensi, Jurnal, Rekap, Rekap Presensi, Profile)
- **User Dropdown**: Profile modal (SweetAlert2), Logout
- **Period Display**: Current 21st-20th period badge
- **Vite Assets**: `@vite(['resources/sass/app.scss', 'resources/js/app.js'])`
- **Feather Icons**: `data-feather="icon-name"` initialization

### 8.3 CSS Architecture
- All custom CSS in `<style>` blocks within Blade files (no separate CSS files)
- `layouts/app.blade.php`: Global variables, utility classes, responsive breakpoints
- `journal/class-detail.blade.php`: Massive inline styles (~900 lines) for attendance grid
- Bootstrap 5 utilities + custom properties

### 8.4 JavaScript Libraries (CDN via `layouts/cdn.blade.php`)
| Library | Version | Purpose |
|---------|---------|---------|
| Font Awesome | 6.4.0 | Icons |
| SweetAlert2 | 11 | Modals, toasts, confirms |
| Leaflet.js | Latest | Maps (absensi) |
| Feather Icons | Latest | UI icons |
| Flatpickr | Latest | Date pickers (Indonesian locale) |
| XLSX.js | Latest | Client-side Excel parsing (teacher import) |

### 8.5 Local JS (`resources/js/app.js`)
- Axios CSRF setup
- Dark mode class toggle
- Bootstrap 5 init (dropdowns, modals, collapse)

---

## 9. Routes Summary (`routes/web.php`)

### 9.1 Public/Root
| Route | Handler | Middleware |
|-------|---------|------------|
| `/` | Redirect to `/login` or role dashboard | - |
| `/login`, `/register`, `/logout` | `Auth::routes()` | `throttle:auth` |

### 9.2 Admin Routes (`auth` + `admin`)
| Route | Controller::Method | Name |
|-------|-------------------|------|
| `/admin` | `AdminController::index` | `admin.index` |
| `/admin/ts` | `JournalController::admin` | `jurnal.admin` |
| `/admin/teacher/table` | `AdminController::teacherTable` | `admin.teacher.table` |
| `POST /admin/import-teachers` | `AdminController::importTeachers` | `import.teachers` |
| `DELETE /admin/user/{id}` | `AdminController::deleteUser` | `admin.deleteUser` |
| `DELETE /admin/absensi/{id}` | `AdminController::deleteAbsensi` | `admin.deleteAbsensi` |
| `POST /admin/absensi/delete-all` | `AdminController::deleteAllAbsensi` | `admin.deleteAllAbsensi` |
| `POST /admin/absensi/delete-by-period` | `AdminController::deleteAbsensiByPeriod` | `admin.deleteAbsensiByPeriod` |
| `DELETE /admin/student/{id}` | `AdminController::deleteStudent` | `delete.student` |
| `PUT /admin/student/{id}/update-grade` | `AdminController::updateGrade` | `admin.student.updateGrade` |
| `PUT /admin/teacher/{id}/mapel` | `AdminController::updateTeacherMapel` | `admin.teacher.updateMapel` |
| `PUT /admin/teacher/{id}` | `AdminController::updateTeacher` | `admin.teacher.update` |
| `PUT /admin/user/{id}/make-admin` | `AdminController::makeAdmin` | `admin.makeAdmin` |
| `PUT /admin/user/{id}/remove-admin` | `AdminController::removeAdmin` | `admin.removeAdmin` |
| `POST /import-students` | `ImportController::import` | `import.students` |
| `GET /journal/export` | `JournalController::export` | `journal.export` |
| `GET /export/pdf/rekap-presensi` | `PdfController::generateRekapPresensi` | `rekap.pdf` |
| `GET /export/pdf/rekap-kbm` | `PdfController::generateRekapKBM` | `rekap.kbm.pdf` |
| `GET /backup` | `BackupController::index` | `backup.index` |
| `POST /backup/save` | `BackupController::save` | `backup.save` |
| `GET /backup/export-zip` | `BackupController::exportGambar` | `backup.exportGambar` |
| `GET /backup/export-waktu` | `BackupController::exportWaktu` | `backup.exportWaktu` |
| `GET /backup/export-lokasi` | `BackupController::exportLokasi` | `backup.exportLokasi` |
| `GET /schedule` | `ScheduleController::index` | `schedule.index` |
| `POST /schedule/import` | `ScheduleController::import` | `schedule.import` |
| `GET /explorer` | `FileManagerController::index` | `explorer.index` |
| `POST /explorer/folder` | `FileManagerController::createFolder` | `explorer.folder` |
| `POST /explorer/upload` | `FileManagerController::uploadFile` | `explorer.upload` |
| `POST /explorer/rename` | `FileManagerController::rename` | `explorer.rename` |
| `DELETE /explorer/delete-file` | `FileManagerController::deleteFile` | `explorer.deleteFile` |
| `DELETE /explorer/delete-folder` | `FileManagerController::deleteFolder` | `explorer.deleteFolder` |

### 9.3 Teacher Routes (`auth` only)
| Route | Controller::Method | Name |
|-------|-------------------|------|
| `/absensi` | `AbsensiController::index` | `absensi.index` |
| `POST /absensi` | `AbsensiController::store` | `absensi.store` |
| `/journal` | `JournalController::selectClass` | `journal.selectClass` |
| `/journal/show` | `JournalController::showClass` | `journal.show` |
| `POST /journal/save-all` | `JournalController::saveAll` | `journal.saveAll` |
| `POST /journal/note` | `JournalController::saveNote` | `journal.saveNote` |
| `/prevSmes` | `JournalController::rekapIndex` | `rekap.index` |
| `/prevSmes/show` | `JournalController::showRekap` | `rekap.show` |
| `/prevSmes/presensi` | `JournalController::showRekapPresensi` | `rekap.showPresensi` |
| `/profile` | `ProfileController::edit` | `profile.edit` |
| `PUT /profile` | `ProfileController::update` | `profile.update_new` |
| `PUT /profile/password` | `ProfileController::updatePassword` | `profile.password.update` |
| `POST /profile/update` | `TeacherController::updateProfile` | `profile.update` (legacy) |

---

## 10. Configuration Files

| File | Purpose |
|------|---------|
| `config/app.php` | Laravel app config (timezone: Asia/Jakarta, locale: id) |
| `config/database.php` | SQLite (dev), MySQL/Postgres (prod) |
| `config/fonnte.php` | WhatsApp API credentials |
| `config/guru.php` | Empty array (legacy placeholder) |
| `config/abbs.php` | Empty array (legacy placeholder) |
| `config/excel.php` | Maatwebsite Excel config |
| `config/captcha.php` | Mews Captcha (unused?) |
| `config/queue.php` | Database queue driver |
| `config/filesystems.php` | Local + public disk for uploads |

---

## 11. External Dependencies & Integrations

### 11.1 Fonnte WhatsApp API
- **Endpoint**: `https://api.fonnte.com/send`
- **Auth**: Header `Authorization: {API_KEY}`
- **Payload**: `target` (phone), `message`, `countryCode=62`, optional `device`
- **Usage**: Teacher check-in confirmation + schedule reminder
- **Error Handling**: Logged to Laravel log, non-blocking

### 11.2 OpenStreetMap Nominatim
- **Endpoint**: `https://nominatim.openstreetmap.org/reverse`
- **Usage**: `GeoHelper::reverseGeocode(lat, lon)` → address string
- **Called from**: `AbsensiController` (not directly visible, likely in frontend JS)

### 11.3 Leaflet.js + OpenStreetMap Tiles
- Map display on absensi page
- Tile layer: `https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png`

---

## 12. Security Considerations

### 12.1 Implemented
- ✅ CSRF protection (Bootstrap.js sets `X-CSRF-TOKEN` on Axios)
- ✅ Password hashing (bcrypt via Laravel default)
- ✅ Path traversal prevention (`FileManagerController::resolvePath()`)
- ✅ Admin middleware on sensitive routes
- ✅ File upload validation (mimes, max 10MB)
- ✅ SQL injection prevention (Eloquent ORM)
- ✅ XSS protection (Blade `{{ }}` escaping)

### 12.2 Gaps / Risks
- ❌ No rate limiting on API endpoints (except auth)
- ❌ No authorization policies (only `is_admin` check)
- ❌ WhatsApp API key in env (should be rotated)
- ❌ No audit logging for admin actions
- ❌ Base64 photos stored in DB (absensis.foto) + filesystem duplication
- ❌ No 2FA for admin accounts

---

## 13. Deployment & Operations

### 13.1 Docker Support
- `Dockerfile`: Multi-stage (Node for build → PHP-FPM + Nginx)
- `composer.json` scripts: `post-create-project` runs migrate + seed

### 13.2 Build Commands
```bash
# Development
composer run dev  # Runs: php artisan serve + queue:listen + npm run dev + phpMyAdmin

# Production Build
npm run build     # Vite production build
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### 13.3 Scheduled Tasks (`routes/console.php`)
- `send:whatsapp-alert` daily at 08:00

### 13.4 Custom Artisan Commands
- `inspire` - Shows quote
- `run:build` - Full production build (migrate:fresh --seed, optimize, npm build)
- `run:genstudents` - Generate dummy students per class
- `run:backup` - Backup DB + files to storage/backups

---

## 14. Testing Status
- **PHPUnit**: Configured (`phpunit.xml`)
- **Tests**: Minimal (1 Feature: `tests/Feature/ExampleTest.php`, 1 Unit: `tests/Unit/ExampleTest.php`)
- **Coverage**: Effectively 0%

---

## 15. Known Technical Debt & Improvement Opportunities

### 15.1 Code Duplication
- **Subject Normalization**: Defined in 3 places (`SubjectHelper`, `TeacherImport`, `ScheduleController`)
- **Period Definitions**: Duplicated in `AdminController` + `BackupController` + `PdfController`
- **Mapel Mapping**: 97 entries in `TeacherImport` vs 95 in `ScheduleController` vs 22 in `SubjectHelper`

### 15.2 Performance Issues
- **N+1 Queries**: `JournalController::showClass()` loads teachers then queries schedules per teacher (partially optimized with `preFetchedTeachers`)
- **Large Inline Views**: `class-detail.blade.php` is 1400+ lines with embedded CSS/JS
- **No Pagination**: Admin dashboard loads all users/students
- **Synchronous WhatsApp**: Blocks check-in response

### 15.3 Architecture Improvements Needed
- Extract `SubjectNormalizer` service class
- Create `PeriodService` for period calculations
- Move inline CSS/JS to Vite-compiled assets
- Implement Laravel Policies for authorization
- Add Queue jobs for WhatsApp + Excel imports
- Add Form Request classes for validation
- Extract `JournalController::showClass()` into multiple services

### 15.4 Data Integrity
- `users.mapel` JSON has no schema validation
- `schedules.teacher` is string not FK (denormalized)
- `absensis.user_id` nullable but `nama` denormalized
- No foreign key on `notes.teacher_id` → `users.id` (only set null on delete)

---

## 16. Environment Variables (`.env.example`)

```env
APP_NAME="Jurnal Kelas"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost
APP_TIMEZONE=Asia/Jakarta

DB_CONNECTION=sqlite
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=jurnal_kelas
# DB_USERNAME=root
# DB_PASSWORD=

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database

FONNTE_API_KEY=
FONNTE_DEVICE_ID=

MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

---

## 17. File Structure Map (Key Files)

```
jurnal-kelas/
├── app/
│   ├── Console/Commands/SendWhatsappAlert.php
│   ├── Exports/JournalExport.php, JournalKBMSheet.php, JournalAttendanceSheet.php
│   ├── Helpers/SubjectHelper.php, GeoHelper.php
│   ├── Http/
│   │   ├── Controllers/ (11 controllers)
│   │   └── Middleware/AdminMiddleware.php, ForceHttpsFromProxy.php, LogSlowRequests.php
│   ├── Imports/TeacherImport.php, StudentsMultiSheetImport.php, UniversalMultiSheetImport.php
│   ├── Models/ (7 models)
│   └── Providers/AppServiceProvider.php
├── bootstrap/app.php (middleware registration)
├── config/ (15 config files)
├── database/
│   ├── factories/UserFactory.php
│   ├── migrations/ (15 migrations)
│   └── seeders/DatabaseSeeder.php, AdminSeeder.php, AdminUserSeeder.php
├── public/
│   ├── build/ (Vite assets)
│   ├── uploads/ (attendance photos)
│   ├── sw.js (service worker)
│   └── offline.html
├── resources/
│   ├── views/ (26 Blade files)
│   │   ├── layouts/app.blade.php, auth.blade.php, cdn.blade.php
│   │   ├── admin/dashboard.blade.php, tsmanager.blade.php, partials/teacher-table.blade.php
│   │   ├── absensi/index.blade.php
│   │   ├── journal/ (6 files)
│   │   ├── pdf/ (2 files)
│   │   ├── backup/ (2 files)
│   │   ├── schedule/index.blade.php
│   │   ├── explorer.blade.php
│   │   └── profile/edit.blade.php
│   ├── js/app.js, bootstrap.js
│   ├── sass/app.scss
│   └── css/app.css
├── routes/web.php, console.php
├── stubs/ (6 Maatwebsite Excel stubs)
├── tests/Feature/ExampleTest.php, tests/Unit/ExampleTest.php
├── vite.config.js
├── package.json
├── composer.json
├── Dockerfile
└── README.md (default Laravel)
```

---

## 18. Acceptance Criteria for Core Features

### 18.1 Teacher Check-in (Absensi)
- [ ] Teacher can access `/absensi` only when authenticated
- [ ] Camera captures photo (base64)
- [ ] GPS coordinates obtained (lat/lon)
- [ ] Server validates geofence ≤ 1km from school
- [ ] Duplicate check prevents same-day double check-in
- [ ] Record saved with photo, location, address, timestamp
- [ ] WhatsApp sent with today's schedule
- [ ] Success redirect to `/success`

### 18.2 Student Attendance Grid
- [ ] Teacher selects class → sees date-specific view
- [ ] 31-day grid with student names
- [ ] Click cell → dropdown (S/I/A/-)
- [ ] Today column highlighted
- [ ] Selected date column highlighted
- [ ] Search filters student rows
- [ ] "Simpan" batches all changes via AJAX
- [ ] Summary row shows S/I/A counts per student

### 18.3 KBM Journal Entry
- [ ] Each subject row has "Keterangan" column
- [ ] Click → input modal/inline edit
- [ ] Saved per subject per date per class
- [ ] Notes persist across date navigation
- [ ] Included in Excel export

### 18.4 Teacher Import
- [ ] Format A (subjects as columns) imports correctly
- [ ] Format B (classes as columns) imports correctly
- [ ] Subject variants normalized to canonical names
- [ ] Class ranges (A-F) expanded
- [ ] Teachers not in Excel are deleted (sync)
- [ ] JSON mapel stored correctly

### 18.5 Schedule Import
- [ ] Multi-sheet Excel parsed
- [ ] Class names from row 1, days from row 2
- [ ] Subjects normalized
- [ ] "Subject - Teacher" split correctly
- [ ] All existing schedules replaced

### 18.6 PDF Exports
- [ ] Rekap Presensi: Landscape A4, student grid with S/I/A, summary totals
- [ ] Rekap KBM: Landscape A4, schedule + attendance + notes per date

---

## 19. Future Roadmap (Out of Scope for Current PRD)

1. **Mobile App** (PWA/React Native) for offline-first attendance
2. **Parent Portal** for viewing child attendance
3. **Analytics Dashboard** with charts (attendance trends, teacher performance)
4. **Multi-school Support** (tenancy)
5. **Academic Year Management** (currently hardcoded year)
6. **Notification Center** (in-app + email + WhatsApp)
7. **REST API** for third-party integrations
8. **Automated Backup** to cloud storage (S3/GDrive)
9. **Audit Log** for all admin actions
10. **Unit/Feature Test Suite** (target 80% coverage)

---

*Document Version: 1.0*  
*Generated: 2026-07-27*  
*Project: Jurnal Kelas - SMP ABBS Surakarta*