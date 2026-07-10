# Frontend

## Build System

**Vite** with `laravel-vite-plugin`. Entry points:
- `resources/sass/app.scss` - SCSS (Bootstrap 5 + custom vars)
- `resources/js/app.js` - JS (Bootstrap 5 + Axios + dark mode init)

Build output: `public/build/`

```bash
npm run dev    # Development with HMR
npm run build  # Production build
```

## CSS Framework

- **Primary:** Bootstrap 5.2.3 (SCSS)
- **Secondary:** Tailwind CSS v4 (configured in `resources/css/app.css` but minimally used)
- **Custom:** Dark mode via CSS custom properties (always-on, no toggle)

### Dark Mode Implementation

Hardcoded dark theme in `layouts/app.blade.php`:
- Background: `#0f172a` (slate-900)
- Cards: `#1e293b` (slate-800)
- Text: `#e2e8f0` (slate-200)
- Primary: `#3b82f6` (blue-500)
- Danger: `#ef4444` (red-500)
- Success: `#22c55e` (green-500)
- Warning: `#f59e0b` (amber-500)

`app.js` adds `class="dark"` to `<html>` on DOMContentLoaded.

## JavaScript Libraries

### CDN (loaded in `layouts/cdn.blade.php`)

| Library | Version | Purpose |
|---|---|---|
| Font Awesome | 6.4.0 | Icons |
| SweetAlert2 | 11 | Modal alerts/confirms |
| Leaflet.js | latest | Maps (absensi location) |
| Feather Icons | latest | Additional icons |
| Flatpickr | latest | Date/time picker + month select + Indonesian locale |
| XLSX.js | latest | Client-side Excel parsing (teacher import) |

### Local (via npm/Vite)

| Library | Purpose |
|---|---|
| Bootstrap 5 JS | Dropdowns, modals, collapse |
| Axios | HTTP requests (CSRF header auto-set) |

## Layouts

### `layouts/app.blade.php` (Main)

- Dark-mode styled navbar with role-based navigation
- **Admin links:** Dashboard, TS Manager, Jurnal, Rekap Semester, Jadwal, Backup, Explorer
- **Teacher links:** Absensi, Jurnal, Rekap
- User dropdown with profile modal (SweetAlert2 popup)
- Mobile bottom navigation bar (non-admin)
- Period detection logic (21st-20th cycle) embedded inline
- `<head>` includes CDN partial, CSRF meta tag, Vite assets

### `layouts/auth.blade.php` (Auth Pages)

- Centered card on dark background
- Abstract gradient decorations
- fadeIn animation

### `layouts/cdn.blade.php` (CDN Partial)

- Included in both layouts
- Loads all CDN libraries
- Contains `<meta charset="utf-8">` and viewport meta

## View Files (26)

### Auth
| File | Purpose |
|---|---|
| `auth/login.blade.php` | Login form with email/password |
| `auth/register.blade.php` | Registration form with teacher list dropdown |
| `auth/verify.blade.php` | Email verification notice |
| `auth/passwords/confirm.blade.php` | Password confirmation |
| `auth/passwords/email.blade.php` | Password reset link request |

### Core Features
| File | Purpose |
|---|---|
| `absensi/index.blade.php` | Teacher check-in form (photo capture, GPS, map) |
| `admin/dashboard.blade.php` | Admin dashboard with stats cards |
| `admin/tsmanager.blade.php` | Teacher-Staff Manager (CRUD table) |
| `admin/partials/teacher-table.blade.php` | Teacher table partial (loaded via AJAX) |
| `journal/select-class.blade.php` | Class selection grid for journal |
| `journal/class-detail.blade.php` | Main journal: attendance grid + KBM editor |
| `journal/rekap-select-class.blade.php` | Class selection for recap |
| `journal/rekap-semester.blade.php` | Semester/daily KBM recap |
| `journal/rekap-presensi.blade.php` | Attendance recap with S/I/A totals |
| `schedule/index.blade.php` | Schedule management (view + import) |
| `backup/index.blade.php` | Attendance grid editor by period |
| `backup/old.blade.php` | Legacy backup view |
| `explorer.blade.php` | File manager for uploads |
| `profile/edit.blade.php` | Profile edit (phone number) |

### PDF Templates
| File | Purpose |
|---|---|
| `pdf/rekap-kbm.blade.php` | PDF layout for KBM recap (landscape A4) |
| `pdf/rekap-presensi.blade.php` | PDF layout for attendance recap (landscape A4) |

### Misc
| File | Purpose |
|---|---|
| `home.blade.php` | Home page |
| `success.blade.php` | Success message page |
| `error.blade.php` | Error message page |
| `welcome.blade.php` | Default Laravel welcome page |

## Client-Side JS Patterns

### AJAX Requests
All use Axios with CSRF token:
```js
axios.post(url, data)
  .then(response => { /* success */ })
  .catch(error => { /* handle error */ });
```

### SweetAlert2
Used extensively for:
- Confirmation dialogs (delete actions)
- Profile editing popup
- Success/error notifications
- Toast notifications

### XLSX.js
Used in `tsmanager.blade.php` for client-side Excel parsing of teacher import files before sending to server.

### Flatpickr
Used for date selection in journal and backup views. Indonesian locale configured. Month select plugin for period selection.

### Leaflet.js
Displays map on absensi form showing current GPS location. Uses OpenStreetMap tiles.

### Service Worker
`public/sw.js` implements cache-first strategy for images in `uploads/` directory for offline access.

## Key Frontend Patterns

1. **No SPA:** Traditional server-rendered Blade with progressive enhancement via JS
2. **No component framework:** Pure Blade templates, no Livewire/Alpine/Vue
3. **Inline JS:** Most Blade files contain `<script>` blocks with feature-specific JS
4. **CSS in layout:** All custom CSS is in `layouts/app.blade.php` `<style>` blocks, not external files
5. **Mobile-first:** Mobile bottom nav, responsive grid layouts
6. **Toast notifications:** SweetAlert2 toasts for save/delete confirmations
