<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    protected $table = 'users';
    protected $fillable = ['name', 'email', 'password', 'mapel', 'phone_num'];
    public $timestamps = false;

    /**
     * Perbaiki JSON jika formatnya salah
     */
    private function fixJson($value)
    {
        if (!is_string($value)) return $value;

        $value = trim($value);

        // Hilangkan kutip luar
        if (str_starts_with($value, '"') && str_ends_with($value, '"')) {
            $value = substr($value, 1, -1);
        }

        // Hilangkan backslash
        $value = stripslashes($value);

        // Single quote -> double quote
        $value = str_replace("'", '"', $value);

        return $value;
    }

    /**
     * Accessor agar $teacher->mapel otomatis rapi
     */
    public function getMapelAttribute($value)
    {
        if (!$value) return [];

        // Perbaiki JSON
        $value = $this->fixJson($value);

        // Decode
        $decoded = json_decode($value, true);

        // Jika masih rusak, kembalikan array kosong
        return $decoded ?: [];
    }

    /**
     * Normalisasi nama mata pelajaran (Sama dengan Schedule Model)
     */
    public static function normalizeSubject($subject)
    {
        if (!$subject || is_array($subject)) return $subject;
        
        $subject = strtoupper(trim($subject));
        
        $mapping = [
            'ICT' => ['ICT', 'KOMPUTER', 'COMPUTER', 'IT'],
            'SPORT' => ['SPORT', 'PJOK', 'OLGA', 'OLAHRAGA', 'PHE'],
            'Civics' => ['CIVIC', 'PKN', 'PPKN', 'CIVICS'],
            'IFE' => ['IFE', 'AGAMA', 'ISLAM', 'PAI', 'BP'],
            'Indonesian' => ['BINDO', 'INDO', 'INDONESIA', 'INDONESIAN', 'B. INDO'],
            'Science' => ['IPA', 'SCIENCE'],
            'Social' => ['SOCIAL', 'IPS'],
            'TKA INDO' => ['TKA INDO', 'TKAINDO', 'TI', 'TKAIND', 'TKA IND'],
            'TKA Mathematics' => ['TM', 'TKA MATH', 'TKAMATH', 'TKAMAT', 'TKA MATHEMATICS'],
            'Quran' => ['QURAN', 'QUR\'AN', 'AL-QURAN', 'AQ'],
            'English' => ['ENGLISH', 'INGGRIS', 'B. INGGRIS', 'ENG'],
            'Mathematics' => ['MATH', 'MATHEMATICS', 'MATEMATIKA', 'MAT'],
        ];

        // 1. Cek Exact Match dulu
        foreach ($mapping as $normalized => $variants) {
            foreach ($variants as $variant) {
                if ($subject === $normalized || $subject === $variant) {
                    return $normalized;
                }
            }
        }

        // 2. Cek Contains
        foreach ($mapping as $normalized => $variants) {
            foreach ($variants as $variant) {
                if (strlen($variant) > 2 && str_contains($subject, $variant)) {
                    return $normalized;
                }
            }
        }

        return $subject;
    }

    /**
     * Mutator agar mapel selalu disimpan sebagai JSON valid dan ternormalisasi
     */
    public function setMapelAttribute($value)
    {
        // Jika input adalah string (misal JSON dari controller/seeder), decode dulu
        if (is_string($value)) {
            $value = json_decode($value, true) ?: [];
        }

        if (!is_array($value)) {
            $this->attributes['mapel'] = json_encode([]);
            return;
        }

        // Fungsi normalisasi rekursif (untuk handle format nested maupun flat)
        $normalize = function($item) use (&$normalize) {
            if (is_array($item)) {
                $newItem = [];
                foreach ($item as $key => $subValue) {
                    $newItem[$key] = $normalize($subValue);
                }
                return $newItem;
            }
            return self::normalizeSubject($item);
        };

        $normalizedMapel = $normalize($value);

        // Simpan sebagai JSON rapi
        $this->attributes['mapel'] = json_encode($normalizedMapel, JSON_UNESCAPED_UNICODE);
    }
}
