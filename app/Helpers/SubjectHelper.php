<?php

namespace App\Helpers;

class SubjectHelper
{
    /**
     * Mapping dari nama mata pelajaran ke nama kanonik.
     */
    private static array $mapping = [
        'ICT' => ['ICT', 'KOMPUTER', 'COMPUTER', 'IT', 'TIK', 'INFORMATIKA'],
        'SPORT' => ['SPORT', 'PJOK', 'OLGA', 'OLAHRAGA', 'PHE', 'SPRT'],
        'Civics' => ['CIVIC', 'PKN', 'PPKN', 'CIVICS', 'CV'],
        'IFE' => ['IFE', 'AGAMA', 'ISLAM', 'PAI', 'BP'],
        'Indonesian' => ['BINDO', 'INDO', 'INDONESIA', 'INDONESIAN', 'B. INDO', 'BI'],
        'Science' => ['IPA', 'SCIENCE', 'SC'],
        'Social' => ['SOCIAL', 'IPS', 'SOC'],
        'TKA INDO' => ['TKA INDO', 'TKAINDO', 'TI', 'TKAIND', 'TKA IND'],
        'TKA Mathematics' => ['TM', 'TKA MATH', 'TKAMATH', 'TKAMAT', 'TKA MATHEMATICS'],
        'Quran' => ['QURAN', 'QUR\'AN', 'AL-QURAN', 'AQ', 'QURAN'],
        'English' => ['ENGLISH', 'INGGRIS', 'B. INGGRIS', 'ENG'],
        'Mathematics' => ['MATH', 'MATHEMATICS', 'MATEMATIKA', 'MAT'],
        'Leadership' => ['LEADERSHIP'],
        'Homeroom Teacher' => ['HOMEROOM TEACHER'],
        'Scout' => ['SCOUT'],
        'Seni Budaya Kesenian' => ['SENI BUDAYA KESENIAN'],
        'Self Development' => ['SELF DEVELOPMENT'],
    ];

    /**
     * Normalize a subject name to its canonical form.
     */
    public static function normalize(?string $subject): ?string
    {
        if (!$subject || is_array($subject)) return $subject;

        $subject = strtoupper(trim($subject));

        // 1. Exact match
        foreach (self::$mapping as $normalized => $variants) {
            foreach ($variants as $variant) {
                if ($subject === $normalized || $subject === $variant) {
                    return $normalized;
                }
            }
        }

        // 2. Contains match (only for variants > 2 chars to avoid false positives)
        foreach (self::$mapping as $normalized => $variants) {
            foreach ($variants as $variant) {
                if (strlen($variant) > 2 && str_contains($subject, $variant)) {
                    return $normalized;
                }
            }
        }

        return $subject;
    }

    /**
     * Get the full mapping dictionary.
     */
    public static function getMapping(): array
    {
        return self::$mapping;
    }
}
