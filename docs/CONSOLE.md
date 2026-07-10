# Console Commands

## Custom Artisan Commands

### `send:whatsapp-alert`

**File:** `app/Console/Commands/SendWhatsappAlert.php`
**Schedule:** Daily at 08:00

Sends WhatsApp attendance reminders to teachers who haven't checked in today.

**Flow:**
1. Get all users with `phone_num` set
2. Check if user already has attendance today (`Absensi::where('user_id', ...)->whereDate('waktu', today)`)
3. If no attendance found, get today's schedule from `Schedule` model
4. Format reminder message with schedule info
5. Send via Fonnte API with 500ms delay between messages

---

## Scheduled Commands (routes/console.php)

### `run:build`

Full production build pipeline.

**Executes:**
1. `php artisan migrate:fresh --seed` - Recreate database with seed data
2. `php artisan config:clear` - Clear config cache
3. `php artisan route:clear` - Clear route cache
4. `php artisan view:clear` - Clear compiled views
5. `php artisan optimize` - Cache config and routes
6. `npm install` - Install Node dependencies
7. `npm run build` - Build frontend assets

**Usage:** `php artisan run:build`

---

### `run:genstudents`

Generates random students for classes 7A through 9F.

**Logic:**
- Creates 30-35 students per class
- Random Indonesian names
- Assigns grade as class name (e.g., "7A")
- Uses `Student::create()` with duplicate checking

**Usage:** `php artisan run:genstudents`

---

### `run:backup`

Creates a password-protected ZIP backup.

**Contents:**
- Database dump (SQL)
- `public/uploads/` directory (attendance photos)

**Output:** ZIP file in `storage/app/`

**Usage:** `php artisan run:backup`

---

### `run:clean`

Clears caches and old log files.

**Executes:**
1. `php artisan cache:clear` - Application cache
2. `php artisan config:clear` - Config cache
3. `php artisan route:clear` - Route cache
4. `php artisan view:clear` - Compiled views
5. `php artisan clear-compiled` - Compiled classes
6. Deletes log files in `storage/logs/` older than 30 days

**Usage:** `php artisan run:clean`

---

### `run:monitor`

Server health check.

**Checks:**
- Database connectivity
- Storage directory writability
- Queue status
- PHP info (version, extensions, memory limit)
- Disk space usage

**Usage:** `php artisan run:monitor`

---

### `inspire`

Displays an inspiring quote (Laravel default).

---

## Scheduling

The `send:whatsapp-alert` command is scheduled in `routes/console.php`:

```php
Schedule::command('send:whatsapp-alert')->dailyAt('08:00');
```

To enable scheduling, add to system crontab:

```bash
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

---

## Useful Debug Commands

```bash
# List all routes
php artisan route:list

# List all models
php artisan model:show User
php artisan model:show Absensi

# Tinker (REPL)
php artisan tinker

# Check migrations status
php artisan migrate:status

# Clear all caches
php artisan cache:clear && php artisan config:clear && php artisan route:clear && php artisan view:clear
```
