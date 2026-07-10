<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_name',
        'day',
        'period',
        'subject',
        'subject_display',
        'teacher',
        'start_time',
        'end_time',
    ];

    protected static function boot()
    {
        parent::boot();

        // Otomatis cari guru jika kosong saat menyimpan
        static::saving(function ($schedule) {
            // Jika mata pelajaran ada tapi guru kosong, cari dari tabel Teacher (User)
            if (empty($schedule->teacher) && !empty($schedule->subject)) {
                $targetSubject = $schedule->subject;
                $targetClass = strtolower($schedule->class_name);
                $targetGrade = substr($targetClass, 0, 1); // e.g. "7"

                // Ambil semua guru yang aktif (bukan admin)
                $teacher = \App\Models\Teacher::where('is_admin', 0)
                    ->get()
                    ->first(function($t) use ($targetSubject, $targetClass, $targetGrade) {
                        $mapel = $t->mapel; // Otomatis didecode oleh accessor di model Teacher
                        
                        if (empty($mapel)) return false;

                        // 1. Format Diberi Key (Dictionary/Import): {"7a": ["ICT"], "7": ["MATH"]}
                        if (!array_is_list($mapel)) {
                            // Cek apakah ada match di kelas spesifik (7a)
                            if (isset($mapel[$targetClass]) && is_array($mapel[$targetClass])) {
                                if (in_array($targetSubject, $mapel[$targetClass])) return true;
                            }
                            // Cek apakah ada match di level grade (7)
                            if (isset($mapel[$targetGrade]) && is_array($mapel[$targetGrade])) {
                                if (in_array($targetSubject, $mapel[$targetGrade])) return true;
                            }
                            
                            // JANGAN ada fallback loop di sini agar tidak "mencuri" mapel kelas lain
                            return false;
                        }

                        // 2. Format List (tambah manual): [{"kelas": "7a", "mapel": "ICT"}] atau ["ICT", "MATH"]
                        if (isset($mapel[0])) {
                            // Jika format-nya [{"kelas": "...", "mapel": "..."}]
                            if (is_array($mapel[0]) && isset($mapel[0]['kelas'])) {
                                foreach ($mapel as $m) {
                                    $mKelas = strtolower($m['kelas'] ?? '');
                                    if (($mKelas === $targetClass || $mKelas === $targetGrade) && 
                                        ($m['mapel'] ?? '') === $targetSubject) {
                                        return true;
                                    }
                                }
                                return false;
                            }
                            
                            // Jika format-nya flat array ["ICT", "MATH"] (Global Teacher)
                            if (is_string($mapel[0])) {
                                return in_array($targetSubject, $mapel);
                            }
                        }

                        return false;
                    });

                if ($teacher) {
                    $schedule->teacher = strtoupper($teacher->name);
                }
            }
        });
    }

    /**
     * Mutator untuk menyimpan subject_display dalam UPPERCASE dan update normalized subject
     */
    public function setSubjectDisplayAttribute($value)
    {
        $display = strtoupper(trim($value ?? ''));
        $this->attributes['subject_display'] = $display;
        
        // Otomatis update 'subject' (hasil normalisasi)
        $this->attributes['subject'] = self::normalizeSubject($display);
    }

    /**
     * Mutator untuk menyimpan nama guru dalam UPPERCASE
     */
    public function setTeacherAttribute($value)
    {
        $this->attributes['teacher'] = $value ? strtoupper(trim($value)) : null;
    }

    /**
     * Normalisasi nama mata pelajaran
     */
    public static function normalizeSubject($subject)
    {
        return \App\Helpers\SubjectHelper::normalize($subject);
    }

    /**
     * Get schedules by class and day
     */
    public static function getScheduleByClassAndDay($className, $day)
    {
        return self::where('class_name', $className)
            ->where('day', $day)
            ->orderBy('period')
            ->get();
    }

    /**
     * Get all classes
     */
    public static function getAllClasses()
    {
        return self::select('class_name')
            ->distinct()
            ->orderBy('class_name')
            ->pluck('class_name');
    }
}
