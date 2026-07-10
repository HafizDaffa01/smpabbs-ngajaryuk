# Setup

## Prerequisites

- PHP 8.2+
- Composer 2.x
- Node.js 18+ & NPM
- SQLite (default) or MySQL

## Installation

```bash
# Clone the repository
git clone <repo-url>
cd jurnal-kelas

# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Create database (SQLite default)
touch database/database.sqlite

# Run migrations and seed
php artisan migrate:fresh --seed

# Build frontend assets
npm run build

# Start the development server
php artisan serve
```

The app will be available at `http://localhost:8000`.

## Environment Variables

Key variables in `.env`:

| Variable | Default | Purpose |
|---|---|---|
| `APP_NAME` | - | Application name |
| `APP_ENV` | `production` | Environment |
| `APP_DEBUG` | `false` | Debug mode |
| `DB_CONNECTION` | - | `sqlite` or `mysql` |
| `DB_DATABASE` | - | Path to SQLite file or DB name |
| `FONNTE_API_KEY` | - | WhatsApp API key (Fonnte service) |
| `SESSION_DRIVER` | `file` | Session storage |
| `QUEUE_CONNECTION` | `database` | Queue driver |
| `MAIL_MAILER` | `log` | Mail driver |

### Optional Services

| Service | Env Variable | Purpose |
|---|---|---|
| Fonnte WhatsApp | `FONNTE_API_KEY` | Send attendance reminders |
| Clockwork | `CLOCKWORK_ENABLE` | Debug profiling |
| Debugbar | `DEBUGBAR_ENABLED` | Debug toolbar |

## Default Admin Account

Created by `DatabaseSeeder`:

| Field | Value |
|---|---|
| Email | `admin@abbs.test` |
| Password | `smpABBS_2025admin` |
| Name | `AdminABBS` |
| Role | Admin (`is_admin=1`) |

## Docker Setup

```bash
# Build and run
docker build -t jurnal-kelas .
docker run -p 8080:8080 jurnal-kelas
```

The Dockerfile uses multi-stage build:
1. **Stage 1 (node:18):** `npm install` + `npm run build`
2. **Stage 2 (php:8.2-fpm):** Composer install, copies built assets

Output runs on port 8080 via PHP-FPM.

## Database Setup

### Fresh Install
```bash
php artisan migrate:fresh --seed
```

### MySQL Setup
Change `.env`:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=jurnal_kelas
DB_USERNAME=root
DB_PASSWORD=
```

Then run `php artisan migrate:fresh --seed`.

### Generate Test Students
```bash
php artisan run:genstudents
```
Creates random students for classes 7A through 9F.

## Production Build

```bash
# Full automated build
php artisan run:build
```

This runs: `migrate:fresh --seed`, `config:clear`, `route:clear`, `view:clear`, `optimize`, `npm install`, `npm run build`.

### Manual Production Steps
```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
npm install --production
npm run build
```

## WhatsApp Setup

1. Register at [Fonnte.com](https://fonnte.com)
2. Get your API key
3. Set `FONNTE_API_KEY` in `.env`
4. Teachers must have `phone_num` set in the users table

The system sends:
- **Check-in confirmation** after teacher attendance
- **Daily reminder** at 08:00 to teachers who haven't checked in (via `send:whatsapp-alert`)

## File Permissions

Ensure writable directories:
```bash
chmod -R 775 storage bootstrap/cache
chmod -R 775 public/uploads
```

## Troubleshooting

| Issue | Solution |
|---|---|
| `Could not find driver` | Install PDO extension: `php -m | grep pdo` |
| `Class not found` | Run `composer dump-autoload` |
| `View not found` | Run `php artisan view:clear` |
| Photos not saving | Check `public/uploads/` permissions |
| WhatsApp not sending | Verify `FONNTE_API_KEY` is set |
