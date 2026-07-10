# Database

## Default Driver

SQLite in development, configurable via `DB_CONNECTION` env. Migrations run against the configured connection.

## Schema

### `users` table

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | auto-increment |
| `name` | string unique | Teacher/admin name |
| `email` | string unique | Login email |
| `phone_num` | string nullable | Phone for WhatsApp notifications |
| `email_verified_at` | timestamp nullable | Email verification |
| `password` | string | Hashed |
| `remember_token` | string nullable | Remember me token |
| `is_admin` | boolean default `false` | Admin flag |
| `mapel` | text nullable | JSON array of canonical subject names |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### `absensis` table

Teacher attendance/check-in records.

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | auto-increment |
| `user_id` | bigint nullable FK | References `users.id` |
| `nama` | string | Teacher name (denormalized) |
| `unit` | string default `'SMP ABBS Surakarta'` | School unit |
| `lokasi` | string | Location name (from geocoding) |
| `alamat` | string nullable | Full address |
| `foto` | longText | Base64-encoded photo |
| `akurasi` | string nullable | GPS accuracy |
| `waktu` | timestamp | Check-in time |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### `students` table

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | auto-increment |
| `name` | string | Student name |
| `progul` | string nullable | Advanced program group |
| `grade` | string indexed | Class name (e.g., "7A", "9F") |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Unique constraint:** `[name, grade]`

### `attendances` table

Daily student attendance records (S/I/A).

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | auto-increment |
| `student_id` | bigint FK | References `students.id` (cascade delete) |
| `day` | tinyInteger | Day of month (1-31) |
| `month` | tinyInteger | Month (1-12) |
| `year` | smallInteger | Year |
| `value` | char(1) | `S` (Sakit), `I` (Izin), `A` (Absen) |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### `notes` table

KBM (Teaching & Learning Activity) journal entries.

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | auto-increment |
| `class` | string(10) | Class name (e.g., "7A") |
| `subject` | string(50) | Canonical subject name |
| `teacher_id` | bigint nullable FK | References `users.id` (set null on delete) |
| `date` | date nullable | Date of the lesson |
| `time` | string(5) | Time slot (e.g., "07:00") |
| `note` | text | Teaching notes/observations |
| `checked` | boolean default `false` | Reviewed flag |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### `schedules` table

Class schedules with subject-teacher mapping.

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | auto-increment |
| `class_name` | string | Class (e.g., "7A") |
| `day` | string | Day of week (English, lowercase) |
| `period` | integer | Period number (ordered) |
| `subject` | string | Canonical subject name |
| `subject_display` | string nullable | Original/uppercase subject display name |
| `teacher` | string nullable | Teacher name (uppercase) |
| `start_time` | time nullable | Period start time |
| `end_time` | time nullable | Period end time |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Index:** `[class_name, day]`

### System Tables

| Table | Purpose |
|---|---|
| `password_reset_tokens` | Password reset tokens |
| `sessions` | Session storage |
| `cache` / `cache_locks` | Cache storage |
| `jobs` / `job_batches` / `failed_jobs` | Queue infrastructure |

## Entity Relationships

```
User (users)
  └── hasMany Absensi (absensis)

Teacher (users, proxy)
  └── hasMany Note (notes)

Student (students)
  └── hasMany Attendance (attendances)

Attendance (attendances)
  └── belongsTo Student (students)

Note (notes)
  └── belongsTo Teacher (users)

Schedule (schedules)
  └── Standalone (teacher is stored as string, not FK)
```

## Migrations Timeline

1. `0001_01_01_000000` - users, password_reset_tokens, sessions
2. `0001_01_01_000001` - cache, cache_locks
3. `0001_01_01_000002` - jobs, job_batches, failed_jobs
4. `2025_08_31_114503` - absensis (basic)
5. `2025_08_31_120238` - users: is_admin
6. `2025_09_01_050412` - absensis: akurasi
7. `2025_10_05_184154` - absensis: user_id
8. `2025_10_05_195346` - absensis: unit
9. `2025_10_05_201313` - absensis: user_id (duplicate, guarded)
10. `2025_11_12_090000` - students
11. `2025_11_13_085359` - users: mapel
12. `2025_11_14_090058` - attendances
13. `2025_12_11_115431` - notes
14. `2026_02_12_143422` - schedules
15. `2026_04_04_073645` - users: phone_num

## Seeders

### `DatabaseSeeder`
- Creates admin: `admin@abbs.test` / `smpABBS_2025admin` with `is_admin=1` and `mapel=["admin"]`

### `AdminSeeder`
- Creates `admin@example.com` / `password` with `is_admin=true`

### `AdminUserSeeder`
- No-op (empty)

## Subject Normalization Map

12 canonical subjects:

| Canonical Name | Accepted Variants |
|---|---|
| `ICT` | ICT, TIK, Komputer, Informatika |
| `SPORT` | PJOK, Penjas, Olahraga, Sport |
| `Civics` | PPKN, PPKn, PKn, Pendidikan Pancasila |
| `IFE` | IFE, IPA, Science (partial) |
| `Indonesian` | Bhs Indonesia, Bahasa Indonesia, Indonesia, Indonesia Language |
| `Science` | IPA, Science, Ilmu Pengetahuan Alam |
| `Social` | IPS, Social, Ilmu Pengetahuan Sosial |
| `TKA INDO` | TKA Indonesia, TKA Bhs, TKA Bhs Indonesia |
| `TKA Mathematics` | TKA Matematika, TKA Math |
| `Quran` | Quran, Tahfidz, Qur'an, Al-Qur'an |
| `English` | Bhs Inggris, Bahasa Inggris, English Language |
| `Mathematics` | Matematika, Mat, Math, Mathematics |
