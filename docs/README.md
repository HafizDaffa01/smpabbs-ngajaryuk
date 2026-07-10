# Jurnal Kelas - Developer Documentation

## Overview

**Jurnal Kelas** is a teacher attendance and class journaling system built for **SMP ABBS Surakarta** (Al Abidin Islamic Junior High School, Surakarta, Indonesia). It manages:

- **Teacher Check-in / Attendance (Absensi)** - GPS-located check-in with photo, WhatsApp notifications
- **Class Schedules** - Import from Excel, auto-assign teachers by subject
- **Student Attendance Tracking** - Daily S/I/A (Sakit/Izin/Absen) grid per class
- **Teaching Activity Journaling (KBM)** - Subject, teacher, time, notes per class per day
- **Data Backup** - Grid-based attendance editor, CSV/ZIP export
- **PDF/Excel Reports** - Semester and monthly recap exports
- **File Manager** - Browse uploaded attendance photos
- **Admin Dashboard** - Teacher/student CRUD, role management

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.2+, Laravel 12 |
| Frontend | Bootstrap 5, Tailwind CSS 4 (minimal), Vanilla JS |
| Database | SQLite (default) / MySQL |
| Auth | Laravel UI (session-based) |
| PDF | DomPDF (barryvdh/laravel-dompdf) |
| Excel | Maatwebsite Excel |
| WhatsApp | Fonnte API |
| Maps | Leaflet.js (reverse geocoding) |
| Alerts | SweetAlert2 |
| Date Picker | Flatpickr |
| Client Excel | XLSX.js |

## Documentation

| File | Description |
|---|---|
| [SETUP.md](SETUP.md) | Installation, environment, first run |
| [ARCHITECTURE.md](ARCHITECTURE.md) | Directory structure, middleware, providers, design decisions |
| [DATABASE.md](DATABASE.md) | Schema, migrations, seeders, relationships |
| [MODELS.md](MODELS.md) | All 7 Eloquent models in detail |
| [CONTROLLERS.md](CONTROLLERS.md) | All 14 controllers, methods, logic |
| [ROUTES.md](ROUTES.md) | Complete route map |
| [FRONTEND.md](FRONTEND.md) | Views, layouts, CDN libs, dark mode |
| [DEPLOYMENT.md](DEPLOYMENT.md) | Docker, production build, backups |
| [CONTRIBUTING.md](CONTRIBUTING.md) | How to edit this codebase (for devs & AI agents) |
| [CONSOLE.md](CONSOLE.md) | Artisan commands and schedules |

## Domain Glossary

| Term | Meaning |
|---|---|
| Absensi | Teacher attendance / check-in |
| KBM | Kegiatan Belajar Mengajar (Teaching & Learning Activity) |
| Mapel | Mata Pelajaran (Subject) |
| Guru | Teacher |
| Kelas | Class (Grade + Section, e.g., "7A") |
| Rekap | Recap / Summary |
| Periode | Attendance period (21st of month N to 20th of month N+1) |
| S / I / A | Sakit (Sick) / Izin (Excused) / Absen (Absent) |
| Progul | Program Unggulan (Advanced Program) |
| TS Manager | Teacher-Staff Manager |

## Quick Reference

```
GET  /                       # Redirect based on role (admin -> /admin, teacher -> /absensi)
GET  /admin                  # Admin dashboard
GET  /absensi                # Teacher check-in form
GET  /journal                # Class journal (select class)
GET  /journal/show?...       # Journal view for a class+date
GET  /prevSmes               # Semester recap (select class)
GET  /schedule               # Schedule management
GET  /backup                 # Backup grid view
GET  /explorer               # File manager
POST /admin/import-teachers  # Import teachers from Excel
POST /import-students        # Import students from Excel
POST /schedule/import        # Import schedules from Excel
```
