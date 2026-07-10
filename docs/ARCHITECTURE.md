# Architecture

## Directory Structure

```
jurnal-kelas/
├── app/
│   ├── Console/Commands/     # 1 custom command (SendWhatsappAlert)
│   ├── Exports/              # 3 Excel export classes
│   ├── Helpers/              # GeoHelper (reverse geocoding)
│   ├── Http/
│   │   ├── Controllers/      # 10 controllers + 4 auth controllers
│   │   └── Middleware/        # 3 custom middleware
│   ├── Imports/              # 4 Excel import classes
│   ├── Models/               # 7 Eloquent models
│   └── Providers/            # AppServiceProvider
├── bootstrap/                # App bootstrap, providers, cache
├── config/                   # 14 config files (includes custom guru.php)
├── database/
│   ├── factories/            # UserFactory
│   ├── migrations/           # 15 migrations
│   └── seeders/              # DatabaseSeeder, AdminSeeder, AdminUserSeeder
├── public/
│   ├── uploads/              # Attendance photos (user-generated)
│   ├── build/                # Vite compiled assets
│   ├── sw.js                 # Service worker (image caching)
│   └── offline.html          # Offline fallback
├── resources/
│   ├── views/                # 26 Blade templates
│   ├── js/                   # app.js, bootstrap.js
│   ├── css/                  # app.css (Tailwind)
│   └── sass/                 # app.scss, _variables.scss
├── routes/
│   ├── web.php               # All web routes
│   └── console.php           # Console commands + schedules
├── stubs/                    # Maatwebsite Excel stubs
├── tests/                    # PHPUnit (minimal: 1 feature, 1 unit test)
└── Dockerfile                # Multi-stage Docker build
```

## User Roles

Two roles exist, both in the `users` table:

| Role | `is_admin` | Access |
|---|---|---|
| **Admin** | `1` | Dashboard, teacher/student CRUD, backup, file explorer, schedule import, journal export, PDF export |
| **Teacher** | `0` | Check-in (absensi), journal editing, recap viewing, profile editing |

Role is checked via `AdminMiddleware` (`auth` + `is_admin`). The `Teacher` model is a **proxy over the `users` table** (same table, different model).

## Middleware

Registered in `bootstrap/app.php`:

| Middleware | Alias | Scope | Purpose |
|---|---|---|---|
| `AdminMiddleware` | `admin` | Route-level | Requires authenticated + `is_admin=1` |
| `ForceHttpsFromProxy` | `forcehttps` | Global | Forces HTTPS when behind Cloudflare Tunnel (checks `X-Forwarded-Proto`) |
| `LogSlowRequests` | - | Global | Logs warnings for requests >1000ms |

## Providers

`AppServiceProvider`:
- **Boot (production only):** Listens to all DB queries, logs warnings for slow queries >500ms
- No policies or gates defined anywhere

## Key Design Decisions

### 1. Teacher = User (Shared Table)
The `users` table stores both admin and teacher accounts. The `Teacher` model sets `$table = 'users'` and `$timestamps = false` to operate on the same data. The `mapel` (subjects) JSON column on users ties teachers to their subjects.

### 2. Period System
Attendance uses a "21st-to-20th" period cycle (e.g., "Januari 21 - Februari 20"). This matches the school's payroll/attendance reporting cycle. Periods are calculated dynamically, not stored.

### 3. Subject Normalization
50+ Indonesian/English subject name variants are normalized to 12 canonical names. This happens in:
- `Teacher::normalizeSubject()` 
- `Schedule::normalizeSubject()` (duplicated)
- `TeacherImport` (Excel import)
- `ScheduleController::import()` (schedule import)

### 4. No Events/Jobs/Notifications
The app uses synchronous processing throughout:
- WhatsApp messages sent directly in controllers/commands
- No queue workers needed (except the `database` queue connection is configured)
- No Laravel notifications, events, or listeners

### 5. Always-On Dark Mode
The app enforces dark mode via CSS custom properties. No light mode toggle exists.

### 6. Inline API Calls
WhatsApp (Fonnte) API calls are made directly via `Http::withHeaders()->post()` in controllers and commands. No dedicated service class wraps the API.

## Request Lifecycle

```
Request
  → ForceHttpsFromProxy (global)
  → LogSlowRequests (global)
  → Route middleware (auth, admin)
  → Controller method
  → Eloquent / DB
  → Blade view (with layouts/app.blade.php wrapper)
  → Response
```

## External Services

| Service | Used For | Config |
|---|---|---|
| Fonnte API | WhatsApp messages | `FONNTE_API_KEY` env |
| OpenStreetMap Nominatim | Reverse geocoding (lat/lon to address) | Hardcoded URL in `GeoHelper` |
| Leaflet.js | Map display on frontend | CDN |

## Security Notes

- **Path Traversal Prevention:** `FileManagerController::resolvePath()` validates all file operations stay within `public/uploads/`
- **CSRF:** Enabled by default (Bootstrap.js sets `X-CSRF-TOKEN` header on Axios)
- **Password Hashing:** Laravel default (bcrypt)
- **No Authorization Policies:** Only `AdminMiddleware` protects admin routes. No per-resource authorization.
- **No Rate Limiting:** Beyond Laravel's default session throttle on login
