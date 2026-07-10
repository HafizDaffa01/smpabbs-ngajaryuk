# Models

7 Eloquent models in `app/Models/`.

---

## User

**File:** `app/Models/User.php`
**Table:** `users`
**Extends:** `Illuminate\Foundation\Auth\User` (Authenticatable)
**Traits:** `HasFactory`, `Notifiable`

### Fields

| Field | Fillable | Cast | Notes |
|---|---|---|---|
| `name` | yes | - | Unique |
| `email` | yes | - | Unique |
| `password` | yes | `hashed` | |
| `phone_num` | yes | - | For WhatsApp |
| `email_verified_at` | no | `datetime` | Hidden |
| `remember_token` | no | - | Hidden |

### Relationships

None defined. Other models reference User via foreign keys.

---

## Teacher

**File:** `app/Models/Teacher.php`
**Table:** `users` (same as User - proxy model)
**Timestamps:** `false`

### Fields

| Field | Fillable | Notes |
|---|---|---|
| `name` | yes | |
| `email` | yes | |
| `password` | yes | |
| `mapel` | yes | JSON, auto-normalized |
| `phone_num` | yes | |

### Accessors

**`mapel` (get):**
Decodes JSON. Auto-fixes broken JSON (single quotes, backslashes, extra quotes). Returns array or null.

### Mutators

**`mapel` (set):**
Accepts string or array. Recursively normalizes all subject names via `normalizeSubject()`. Stores as JSON.

### Static Methods

**`normalizeSubject($subject)`:**
Maps 50+ variants to 12 canonical names. Strategy:
1. Uppercase input
2. Exact match against dictionary
3. Contains-match against dictionary keys

**Canonical names:** ICT, SPORT, Civics, IFE, Indonesian, Science, Social, TKA INDO, TKA Mathematics, Quran, English, Mathematics

### Private Methods

**`fixJson($value)`:**
Strips outer quotes, stripslashes, converts single quotes to double quotes.

---

## Absensi

**File:** `app/Models/Absensi.php`
**Table:** `absensis`
**Traits:** `HasFactory`
**Timestamps:** `true`

### Fields

| Field | Fillable | Cast | Notes |
|---|---|---|---|
| `user_id` | yes | - | FK -> users.id |
| `nama` | yes | - | Teacher name (denormalized) |
| `unit` | yes | - | Default: 'SMP ABBS Surakarta' |
| `lokasi` | yes | - | Location name |
| `alamat` | yes | - | Full address |
| `foto` | yes | - | Base64 photo |
| `waktu` | yes | `datetime` | Check-in timestamp |

### Relationships

- `user()` -> `belongsTo(User::class)`

---

## Student

**File:** `app/Models/Student.php`
**Table:** `students`

### Fields

| Field | Fillable | Notes |
|---|---|---|
| `name` | yes | |
| `grade` | yes | Class name (e.g., "7A") |
| `progul` | no | Advanced program |

### Relationships

- `attendance()` -> `hasMany(Attendance::class)`

### Constraints

Unique index on `[name, grade]`.

---

## Attendance

**File:** `app/Models/Attendance.php`
**Table:** `attendances`

### Fields

| Field | Fillable | Notes |
|---|---|---|
| `student_id` | yes | FK -> students.id (cascade) |
| `day` | yes | 1-31 |
| `month` | yes | 1-12 |
| `year` | yes | |
| `value` | yes | `S`, `I`, or `A` |

### Relationships

- `student()` -> `belongsTo(Student::class)`

---

## Note

**File:** `app/Models/Note.php`
**Table:** `notes`

### Fields

| Field | Fillable | Notes |
|---|---|---|
| `class` | yes | Class name (e.g., "7A") |
| `subject` | yes | Canonical subject name |
| `teacher_id` | yes | FK -> users.id (set null) |
| `date` | yes | Lesson date |
| `time` | yes | Time slot string |
| `note` | yes | Teaching notes |
| `checked` | yes | Review flag (bool) |

### Relationships

- `teacher()` -> `belongsTo(Teacher::class)` (which is the users table)

---

## Schedule

**File:** `app/Models/Schedule.php`
**Table:** `schedules`
**Traits:** `HasFactory`
**Timestamps:** `true`

### Fields

| Field | Fillable | Notes |
|---|---|---|
| `class_name` | yes | e.g., "7A" |
| `day` | yes | English lowercase day name |
| `period` | yes | Integer, ordered |
| `subject` | yes | Canonical subject name |
| `subject_display` | yes | Uppercase display name |
| `teacher` | yes | Teacher name (uppercase) |
| `start_time` | yes | |
| `end_time` | yes | |

### Boot Logic

On `saving` event: If `teacher` is empty but `subject` is provided, auto-lookup teacher from `Teacher` model by matching subject to the teacher's `mapel` JSON field. Supports both dictionary format (`{"7a": ["ICT"]}`) and list format (`[{"kelas": "7a", "mapel": "ICT"}]`).

### Mutators

- **`subject_display` (set):** Stores UPPERCASE, auto-sets `subject` via `normalizeSubject()`
- **`teacher` (set):** Stores UPPERCASE

### Static Methods

- **`normalizeSubject($subject)`** - Same logic as Teacher (duplicated code)
- **`getScheduleByClassAndDay($className, $day)`** - Query by class+day, ordered by period
- **`getAllClasses()`** - Returns distinct class names

### Relationships

None. Teacher is stored as a plain string, not a foreign key.
