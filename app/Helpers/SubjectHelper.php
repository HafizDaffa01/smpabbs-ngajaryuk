<?php

namespace App\Helpers;

class SubjectHelper
{
    /**
     * Mapping dari nama mata pelajaran ke nama kanonik.
     */
    private static array $mapping = [
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
