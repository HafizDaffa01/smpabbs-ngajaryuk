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
