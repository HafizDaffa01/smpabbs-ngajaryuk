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

        // Jika sudah berupa array (misal dari setAttribute atau JSON decode sebelumnya)
        if (is_array($value)) return $value;

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
        return \App\Helpers\SubjectHelper::normalize($subject);
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
