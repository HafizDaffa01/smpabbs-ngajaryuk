<?php

namespace App\Imports;

use App\Models\Teacher;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TeacherImport implements ToCollection, WithHeadingRow
{
    protected array $mapelMapping = [
        // Agama
        'pai' => 'IFE',
        'agama' => 'IFE',
        'islam' => 'IFE',
        'pendidikan agama islam' => 'IFE',
        'pendidikan agama' => 'IFE',
        'btq' => 'Quran',
        'quran' => 'Quran',
        "qur'an" => 'Quran',
        'tahfidz' => 'Quran',

        // Exact
        'math' => 'Mathematics',
        'mtk' => 'Mathematics',
        'matematika' => 'Mathematics',
        'mat' => 'Mathematics',

        'science' => 'Science',
        'ipa' => 'Science',
        'biologi' => 'Science',
        'fisika' => 'Science',

        // Social
        'ips' => 'Social',
        'social' => 'Social',
        'sejarah' => 'Social',
        'geografi' => 'Social',
        'ekonomi' => 'Social',

        // Language
        'english' => 'English',
        'inggris' => 'English',
        'bahasa inggris' => 'English',
        'b.inggris' => 'English',
        'b. inggris' => 'English',
        'indonesian' => 'Indonesian',
        'indo' => 'Indonesian',
        'indonesia' => 'Indonesian',
        'bahasa indo' => 'Indonesian',
        'bahasa indonesia' => 'Indonesian',
        'b.indo' => 'Indonesian',
        'b. indo' => 'Indonesian',
        'b.indonesia' => 'Indonesian',

        // Character / Civics
        'pkn' => 'Civics',
        'ppkn' => 'Civics',
        'civics' => 'Civics',
        'pendidikan pancasila' => 'Civics',
        'leadership' => 'Leadership',
        'pramuka' => 'Leadership',

        // Physical Education
        'sport' => 'SPORT',
        'pjok' => 'SPORT',
        'olahraga' => 'SPORT',
        'penjas' => 'SPORT',
        'olga' => 'SPORT',

        // Technology
        'ict' => 'ICT',
        'computer' => 'ICT',
        'komputer' => 'ICT',
        'tik' => 'ICT',
        'informatika' => 'ICT',

        // TKA
        'tka math' => 'TKA Mathematics',
        'tka mtk' => 'TKA Mathematics',
        'tm' => 'TKA Mathematics',
        'tka ind' => 'TKA INDO',
        'tka indo' => 'TKA INDO',
        'ti' => 'TKA INDO',

        // Local Wisdom
        'bahasa jawa' => 'Javanese',
        'b. jawa' => 'Javanese',
        'jawa' => 'Javanese',
    ];

    private function mapSubject($rawSubject)
    {
        $rawSubject = trim($rawSubject);
        if ($rawSubject === '' || $rawSubject === '-') return null;

        // Remove trailing numbers appended by Excel row duplication
        $key = preg_replace('/\s+\d+$/', '', strtolower($rawSubject));
        
        // If it's a number only, it's probably not a mapel name
        if (is_numeric($key)) return null;

        return $this->mapelMapping[$key] ?? preg_replace('/\s+\d+$/', '', $rawSubject);
    }

    private function flattenClasses(array $items)
    {
        $flattened = [];
        $uniqueKeys = [];

        foreach ($items as $item) {
            $mapel = $item['mapel'];
            $kelasRaw = $item['kelas'];

            // Expand class range e.g. "A-F" -> "ABCDEF"
            $kelasExpanded = preg_replace_callback('/([A-Za-z])-([A-Za-z])/', function($m) {
                $start = strtoupper($m[1]);
                $end = strtoupper($m[2]);
                if ($start <= $end) {
                    $res = '';
                    for ($c = ord($start); $c <= ord($end); $c++) {
                        $res .= chr($c);
                    }
                    return $res;
                }
                return $m[0];
            }, $kelasRaw);

            // Find all patterns of Number + Letters (e.g. "7 ABC", "8A", "7")
            preg_match_all('/([789])\s*([A-Za-z]+)?/', $kelasExpanded, $matches, PREG_SET_ORDER);
            
            if (!empty($matches)) {
                foreach ($matches as $m) {
                    $grade = $m[1];
                    $lettersMatch = $m[2] ?? '';
                    
                    if ($lettersMatch === '') {
                        $kelasFinal = $grade;
                        $key = $mapel . '|' . $kelasFinal;
                        if (!isset($uniqueKeys[$key])) {
                            $uniqueKeys[$key] = true;
                            $flattened[] = ['mapel' => $mapel, 'kelas' => $kelasFinal];
                        }
                    } else {
                        $letters = str_split(strtoupper(str_replace(' ', '', $lettersMatch)));
                        foreach ($letters as $l) {
                            $kelasFinal = $grade . $l;
                            $key = $mapel . '|' . $kelasFinal;
                            if (!isset($uniqueKeys[$key])) {
                                $uniqueKeys[$key] = true;
                                $flattened[] = ['mapel' => $mapel, 'kelas' => $kelasFinal];
                            }
                        }
                    }
                }
            } else {
                if (trim($kelasExpanded) !== '' && trim($kelasExpanded) !== '-') {
                    $kelasFinal = trim($kelasExpanded);
                    $key = $mapel . '|' . $kelasFinal;
                    if (!isset($uniqueKeys[$key])) {
                        $uniqueKeys[$key] = true;
                        $flattened[] = ['mapel' => $mapel, 'kelas' => $kelasFinal];
                    }
                }
            }
        }

        return $flattened;
    }

    private function detectFormat(array $headers, Collection $rows)
    {
        // ==== DETECT FORMAT B ====
        // Headers are classes (e.g. '7a', '8b', '9')
        $hasRombel = false;
        foreach ($headers as $h) {
            if (preg_match('/^[789][a-z]?$/i', (string)$h)) {
                $hasRombel = true;
                break;
            }
        }

        if ($hasRombel) {
            $keywords = ['matematika', 'ipa', 'ips', 'pkn', 'ict', 'pjok', 'indonesian', 'english', 'pai', 'quran', 'tka'];
            $found = false;
            foreach ($rows->take(10) as $row) {
                foreach ($row as $key => $cell) {
                    if (is_scalar($cell)) {
                        $cellLower = strtolower(trim((string)$cell));
                        foreach ($keywords as $k) {
                            if (strpos($cellLower, $k) !== false) {
                                $found = true;
                                break 3;
                            }
                        }
                    }
                }
            }
            if ($found) return 'B';
        }

        // ==== DETECT FORMAT A ====
        // Headers are subjects (e.g. 'math', 'ipa', 'ips')
        $required_A = ['math', 'ipa', 'ips', 'pkn', 'ict', 'pjok', 'indonesian', 'english', 'pai', 'quran'];
        $matchCount = 0;
        foreach ($required_A as $req) {
            foreach ($headers as $h) {
                if (strpos(strtolower((string)$h), $req) !== false) {
                    $matchCount++;
                    break;
                }
            }
        }

        if ($matchCount >= 2) { 
            // Validasi Isi (berupa kelas: "7A", "8 BC", dll)
            $found = false;
            foreach ($rows->take(10) as $row) {
                foreach ($row as $key => $cell) {
                    // Ignore predefined non-subject columns
                    if (in_array(strtolower($key), ['nama', 'email', 'password', 'no', 'name', 'number', 'num', 'hp', 'telepon', 'wa', 'whatsapp', 'phone'])) continue;

                    if (is_scalar($cell)) {
                        $cellStr = (string)$cell;
                        if (preg_match('/[789]\s?[a-f]/i', $cellStr)) {
                            $found = true;
                            break 2;
                        }
                    }
                }
            }
            if ($found) return 'A';
        }

        return null;
    }

    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) {
            return;
        }

        $headers = array_keys($rows->first()->toArray());
        $formatType = $this->detectFormat($headers, $rows);

        Log::info("Teacher Import format detected: " . ($formatType ?? 'Unknown/Fallback'));

        DB::transaction(function () use ($rows, $formatType) {
            foreach ($rows as $row) {
                $nama = $row['nama'] ?? $row['name'] ?? null;
                $email = $row['email'] ?? null;
                $password = $row['password'] ?? null;
                $phone = $row['hp'] ?? $row['telepon'] ?? $row['wa'] ?? $row['whatsapp'] ?? $row['phone'] ?? $row['num'] ?? $row['number'] ?? null;

                if (!$nama) {
                    continue;
                }

                if (!$email || !$password) {
                    Log::warning('Row di-skip karena tidak ada email/password untuk ' . $nama);
                    continue;
                }

                $user = Teacher::firstOrNew(['email' => $email]);
                $user->name = $nama;
                $user->password = Hash::make($password);
                $user->is_admin = 0;
                $user->phone_num = $phone;

                $rawItems = [];

                if ($formatType === 'B' || $formatType === null) {
                    // Fallback to Format B logic
                    foreach ($row as $key => $val) {
                        if ($val && preg_match('/^[789][a-z]?$/i', (string)$key)) {
                            $kelas = strtoupper((string)$key);
                            $items = preg_split('/\s*(?:&|\+|dan)\s*/i', $val);
                            foreach ($items as $m) {
                                $mapped = $this->mapSubject($m);
                                if ($mapped && trim($mapped) !== '' && trim($mapped) !== '-') {
                                    $rawItems[] = [
                                        'kelas' => $kelas,
                                        'mapel' => $mapped
                                    ];
                                }
                            }
                        }
                    }
                } elseif ($formatType === 'A') {
                    foreach ($row as $key => $val) {
                        if (in_array(strtolower($key), ['no', 'nama', 'name', 'email', 'password', 'number', 'num', 'hp', 'telepon', 'wa', 'whatsapp', 'phone'])) continue;
                        
                        if (is_scalar($val)) {
                            $strVal = (string)$val;
                            if (trim($strVal) !== '' && trim($strVal) !== '-') {
                                $mapped = $this->mapSubject(str_replace('_', ' ', $key));
                                if ($mapped && trim($mapped) !== '') {
                                    $rawItems[] = [
                                        'mapel' => $mapped,
                                        'kelas' => trim($strVal)
                                    ];
                                }
                            }
                        }
                    }
                }

                $user->mapel = $this->flattenClasses($rawItems);
                $user->save();
            }
        });
    }
}
