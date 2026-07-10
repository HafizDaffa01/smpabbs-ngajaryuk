# Contributing

Guide for developers and AI agents editing this codebase.

## Codebase Identity

This is a **Laravel 12** PHP application for teacher attendance and class journaling at an Indonesian school. It uses **Blade** templates, **Bootstrap 5** dark mode UI, and **Eloquent ORM**. There is no SPA framework, no API layer, and no queue workers.

## Before Making Changes

1. **Read the docs** - Check [MODELS.md](MODELS.md), [CONTROLLERS.md](CONTROLLERS.md), [ROUTES.md](ROUTES.md) for existing structure
2. **Run the app** - `php artisan serve` + `npm run dev`
3. **Check the database** - `php artisan tinker` to inspect models
4. **Verify routes** - `php artisan route:list`

## Code Conventions

### PHP / Laravel

- **PHP 8.2+** features allowed (readonly, enums, etc.)
- **No type hints required** on controller methods (existing code doesn't use them)
- **Eloquent over Query Builder** - Use models and relationships
- **No API Resources** - Controllers return views or JSON directly
- **No Form Requests** - Validation done inline in controller methods
- **No Policies/Gates** - Admin check via middleware only
- **Env values** via `env()` helper directly (not config files) for API keys

### Naming

| Item | Convention | Example |
|---|---|---|
| Models | Singular PascalCase | `Teacher`, `Absensi` |
| Controllers | PascalCase + Controller | `JournalController` |
| Tables | Plural snake_case | `attendances`, `absensis` |
| Columns | snake_case | `user_id`, `phone_num` |
| Routes | Dot notation names | `journal.export`, `admin.index` |
| Blade files | Dot notation paths | `journal.class-detail` |
| Views dir | Singular/feature | `journal/`, `admin/`, `backup/` |

### Database

- **Migrations** go in `database/migrations/` with timestamp prefix
- **Foreign keys** use `constrained()->cascadeOnDelete()` or `nullOnDelete()`
- **No model factories** for most models (only UserFactory exists)
- **Seeders** create test data, not full datasets

### Frontend

- **Dark mode is mandatory** - Use slate/navy color palette
- **Bootstrap 5 classes** - Use `btn-primary`, `card`, `table-dark`, etc.
- **No Alpine.js/Livewire** - Use vanilla JS with Axios for AJAX
- **SweetAlert2** for all alerts/confirms
- **Inline `<script>`** in Blade files for feature-specific JS
- **CDN libraries** in `layouts/cdn.blade.php`

### Styles

Custom CSS goes in `layouts/app.blade.php` inside `<style>` blocks. Use CSS custom properties:

```css
--bg-primary: #0f172a;    /* Main background */
--bg-secondary: #1e293b;  /* Cards */
--bg-tertiary: #334155;   /* Input fields */
--text-primary: #e2e8f0;  /* Main text */
--text-secondary: #94a3b8; /* Muted text */
--accent: #3b82f6;        /* Links, primary buttons */
```

## Common Tasks

### Adding a New Model

1. Create migration: `php artisan make:migration create_xxx_table`
2. Create model: `php artisan make:model Xxx`
3. Run migration: `php artisan migrate`
4. Add relationships to existing models if needed
5. Add controller methods or new controller
6. Register routes in `routes/web.php`
7. Create Blade views in `resources/views/xxx/`

### Adding a New Admin Feature

1. Create controller in `app/Http/Controllers/`
2. Add `middleware: auth, admin` to routes
3. Add nav link in `layouts/app.blade.php` (admin section)
4. Create Blade view in `resources/views/`
5. Use dark mode CSS classes

### Adding a New Teacher Feature

1. Create controller in `app/Http/Controllers/`
2. Add `middleware: auth` to routes
3. Add nav link in `layouts/app.blade.php` (teacher section)
4. Create Blade view in `resources/views/`
5. Add mobile bottom nav link if applicable

### Modifying the Subject Normalization

**WARNING:** Subject normalization is duplicated in 3 places:
- `app/Models/Teacher.php` - `normalizeSubject()`
- `app/Models/Schedule.php` - `normalizeSubject()` (identical copy)
- `app/Imports/TeacherImport.php` - inline mapping

If you change normalization logic, update ALL three locations. Consider extracting to a shared service class.

### Adding Excel Import/Export

1. Create class in `app/Imports/` or `app/Exports/`
2. Implement Maatwebsite Excel interfaces (`ToModel`, `FromCollection`, etc.)
3. Use existing stubs in `stubs/` as reference
4. Controller method calls `$file->import(new ImportClass)` or `Excel::download(new ExportClass, filename)`

### Adding WhatsApp Messages

Use the Fonnte API pattern from `AbsensiController@store()`:

```php
Http::withHeaders([
    'Authorization' => env('FONNTE_API_KEY'),
])->post('https://api.fonnte.com/send', [
    'target' => $phone_number,
    'message' => $message_text,
]);
```

## Testing

```bash
php artisan test                          # Run all tests
php artisan test --filter=ExampleTest     # Run specific test
```

Tests use SQLite in-memory by default. Minimal test coverage exists.

## Files That Change Often

| File | Reason |
|---|---|
| `routes/web.php` | New features need routes |
| `resources/views/layouts/app.blade.php` | Nav links, CSS |
| `app/Http/Controllers/*.php` | Business logic |
| `database/migrations/*.php` | Schema changes |
| `config/app.php` | App name, timezone |

## Key Gotchas

1. **Teacher model = users table** - Don't create a separate `teachers` table
2. **No `progul` migration** - The `progul` column on students exists but has no dedicated migration (added manually or via seeder)
3. **Duplicate migration** - `add_user_id_to_absensis_table` exists twice (both guarded with `hasColumn`)
4. **Mapel JSON format varies** - Can be `["ICT", "SPORT"]` or `[{"kelas": "7a", "mapel": "ICT"}]`
5. **Subject normalization duplicated** - Same code in Teacher, Schedule, and TeacherImport
6. **No authorization policies** - Only role-based middleware, no per-record authorization
7. **Photos stored as base64** in DB and as files in `public/uploads/`
8. **Period system is dynamic** - Calculated, not stored. Based on 21st-to-20th cycle
9. **Always dark mode** - No light mode, no theme toggle
10. **CDN dependencies** - Many frontend libs loaded from CDN, not npm

## Environment

| Tool | Version |
|---|---|
| PHP | 8.2+ |
| Laravel | 12.x |
| Node.js | 18+ |
| NPM | 9+ |
| Composer | 2.x |

## Branch Strategy

- `main` - Production-ready code
- Feature branches for new features
- No CI/CD pipeline configured
