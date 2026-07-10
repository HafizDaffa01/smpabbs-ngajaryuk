# Deployment

## Docker

### Dockerfile

Multi-stage build:

```dockerfile
# Stage 1: Frontend build
FROM node:18 AS frontend
RUN npm install && npm run build

# Stage 2: PHP application
FROM php:8.2-fpm
# System deps, PHP extensions, Composer
# Copies app + built frontend from Stage 1
# Runs composer install --no-dev
# CMD: php-fpm
```

### Build & Run

```bash
docker build -t jurnal-kelas .
docker run -p 8080:8080 jurnal-kelas
```

### Docker with MySQL

For production with MySQL, use docker-compose:

```yaml
version: '3.8'
services:
  app:
    build: .
    ports:
      - "8080:8080"
    environment:
      - DB_CONNECTION=mysql
      - DB_HOST=db
      - DB_PORT=3306
      - DB_DATABASE=jurnal_kelas
      - DB_USERNAME=root
      - DB_PASSWORD=secret
    depends_on:
      - db

  db:
    image: mysql:8.0
    environment:
      - MYSQL_ROOT_PASSWORD=secret
      - MYSQL_DATABASE=jurnal_kelas
    volumes:
      - db_data:/var/lib/mysql

volumes:
  db_data:
```

## Production Checklist

### Server Requirements
- PHP 8.2+ with extensions: pdo, pdo_mysql, mbstring, zip, gd (for captcha)
- MySQL 8.0+ or SQLite
- Node.js 18+ (for build)
- Composer 2.x
- Web server (Nginx/Apache) or PHP built-in server

### Deployment Steps

```bash
# 1. Pull latest code
git pull origin main

# 2. Install dependencies
composer install --no-dev --optimize-autoloader
npm install --production

# 3. Environment
cp .env.example .env  # if new
php artisan key:generate
# Edit .env with production values

# 4. Database
php artisan migrate --force

# 5. Cache everything
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# 6. Build frontend
npm run build

# 7. Set permissions
chmod -R 775 storage bootstrap/cache
chmod -R 775 public/uploads

# 8. Create admin user (if not seeded)
php artisan tinker
# > User::create(['name'=>'Admin','email'=>'admin@example.com','password'=>Hash::make('secure-password'),'is_admin'=>1,'mapel'=>'["admin"]']);
```

### Quick Deploy Script

```bash
php artisan run:build
```

This runs: `migrate:fresh --seed`, cache clearing, optimization, and npm build.

## Backups

### Manual Backup

```bash
php artisan run:backup
```

Creates a password-protected ZIP with:
- Database dump
- `public/uploads/` directory

### Backup Location

Default: `storage/app/` (configured in `config/filesystems.php`)

### Automated Backups

Set up a cron job:
```bash
0 2 * * * cd /path/to/app && php artisan run:backup
```

## Cron Jobs

Required for scheduled tasks:

```bash
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

This runs the daily WhatsApp alert at 08:00 (`send:whatsapp-alert`).

## Queue Workers

If using database queue (default config):

```bash
php artisan queue:work --sleep=3 --tries=3
```

Note: Currently no queued jobs exist, but the infrastructure is configured.

## Monitoring

```bash
php artisan run:monitor
```

Checks: database connectivity, storage writability, queue status, PHP info, disk space.

## Log Management

```bash
php artisan run:clean
```

Clears: application cache, config cache, route cache, view cache, compiled classes, log files older than 30 days.

## Reverse Proxy / HTTPS

The app includes `ForceHttpsFromProxy` global middleware that forces HTTPS when the `X-Forwarded-Proto: https` header is present. This is designed for Cloudflare Tunnel or similar proxy setups.

### Nginx Config (Reference)

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/jurnal-kelas/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## Performance Notes

- **No Redis required** (file-based session/cache by default)
- **No queue worker required** (no queued jobs)
- **SQLite suitable for small deployments** (single teacher attendance, <100 concurrent users)
- **MySQL recommended** for production multi-user environments
- **Slow query logging** enabled in production (queries >500ms logged as warnings)
- **Slow request logging** enabled globally (requests >1000ms logged as warnings)
