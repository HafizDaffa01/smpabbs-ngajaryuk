<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Absensi;
use Illuminate\Support\Facades\File;
use App\Models\Student;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Hash;
use App\Imports\TeacherImport;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class AdminController extends Controller
{
    public function index(): View
    {
        $users = User::where('name', '!=', 'AdminABBS')->get();
        $absensiCount = Absensi::count();
        
        // Optimized student fetching
        $students = Student::orderBy('name')->get();
        $studentsByClass = $students->groupBy('grade');
        
        $classes = Student::select('grade')->distinct()->orderBy('grade')->pluck('grade');
        $totalClasses = $classes->filter(fn($c) => $c !== 'Kelas')->count();
        $totalStudents = $students->filter(fn($s) => $s->name !== 'Nama')->count();
        $totalTeachers = $users->where('name', '!=', 'AdminABBS')->count();

        // ====== LOGIKA PENENTUAN PERIODE OTOMATIS ======
        $periods = [
            'jan_feb' => ['label' => 'Januari 21 - Februari 20', 'start' => [1, 21], 'end' => [2, 20]],
            'feb_mar' => ['label' => 'Februari 21 - Maret 20', 'start' => [2, 21], 'end' => [3, 20]],
            'mar_apr' => ['label' => 'Maret 21 - April 20', 'start' => [3, 21], 'end' => [4, 20]],
            'apr_mei' => ['label' => 'April 21 - Mei 20', 'start' => [4, 21], 'end' => [5, 20]],
            'mei_jun' => ['label' => 'Mei 21 - Juni 20', 'start' => [5, 21], 'end' => [6, 20]],
            'jun_jul' => ['label' => 'Juni 21 - Juli 20', 'start' => [6, 21], 'end' => [7, 20]],
            'jul_agu' => ['label' => 'Juli 21 - Agustus 20', 'start' => [7, 21], 'end' => [8, 20]],
            'agu_sep' => ['label' => 'Agustus 21 - September 20', 'start' => [8, 21], 'end' => [9, 20]],
            'sep_okt' => ['label' => 'September 21 - Oktober 20', 'start' => [9, 21], 'end' => [10, 20]],
            'okt_nov' => ['label' => 'Oktober 21 - November 20', 'start' => [10, 21], 'end' => [11, 20]],
            'nov_des' => ['label' => 'November 21 - Desember 20', 'start' => [11, 21], 'end' => [12, 20]],
            'des_jan' => ['label' => 'Desember 21 - Januari 20', 'start' => [12, 21], 'end' => [1, 20]],
        ];

        $today = now();
        $currentKey = 'jan_feb';

        foreach ($periods as $key => $range) {
            $start = Carbon::create($today->year, $range['start'][0], $range['start'][1])->startOfDay();
            $endMonth = $range['end'][0];
            $endYear = $endMonth < $range['start'][0] ? $today->year + 1 : $today->year;
            $end = Carbon::create($endYear, $endMonth, $range['end'][1])->endOfDay();

            if ($today->between($start, $end)) {
                $currentKey = $key;
                break;
            }
        }

        return view('admin.dashboard', compact(
            'users', 
            'absensiCount', 
            'totalClasses', 
            'totalStudents', 
            'totalTeachers',
            'classes', 
            'studentsByClass',
            'currentKey'
        ));
    }

    public function teacherTable(): View
    {
        $users = \App\Models\Teacher::where('is_admin', 0)->get();
        return view('admin.partials.teacher-table', compact('users'));
    }

    public function importTeachers(Request $request): JsonResponse
    {
        $request->validate([
            'excel' => 'required|file|mimes:xlsx,xls,csv|max:10240'
        ]);

        try {
            Excel::import(new TeacherImport, $request->file('excel'));

            return response()->json([
                'status' => 'success',
                'message' => 'Import guru berhasil!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'error' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
            ], 500);
        }
    }


    public function updateTeacher(Request $request, $id): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$id,
            'phone_num' => 'nullable|string|max:20'
        ]);

        $user = User::findOrFail($id);
        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone_num = $request->phone_num;
        
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Data guru berhasil diperbarui.'
        ]);
    }

    public function updateTeacherMapel(Request $request, $id): JsonResponse
    {
        $request->validate([
            'mapel' => 'required|array'
        ]);

        $user = User::findOrFail($id);
        $user->mapel = json_encode($request->mapel);
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Data mata pelajaran guru berhasil diperbarui.'
        ]);
    }


    public function updateGrade(Request $request, $id): JsonResponse
    {
        $request->validate([
            'grade' => 'required|string|max:15'
        ]);

        $student = Student::find($id);
        if (!$student) {
            return response()->json([
                'status' => 'error',
                'message' => 'Siswa tidak ditemukan'
            ], 404);
        }

        $student->grade = $request->grade;
        $student->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Kelas Siswa berhasil diperbarui.'
        ]);
    }

    public function deleteAllAbsensi(Request $request): RedirectResponse
    {
        $withImage = $request->query('with_image', 0);
        
        Absensi::chunkById(100, function ($absensis) use ($withImage) {
            foreach ($absensis as $absen) {
                // Hapus data absensi dari database
                $absen->delete();
            }
        });

        // Jika hapus gambar aktif, bersihkan juga folder database uploads tapi hanya gambar
        if ($withImage == 1) {
            $uploadsPath = public_path('uploads');
            if (File::exists($uploadsPath)) {
                $allFiles = File::allFiles($uploadsPath);
                $allowedExt = ['png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp', 'svg'];

                foreach ($allFiles as $file) {
                    $ext = strtolower($file->getExtension());
                    if (in_array($ext, $allowedExt)) {
                        File::delete($file->getRealPath());
                    }
                }
            }
        }

        return back()->with(
            'success',
            $withImage == 1
                ? 'Semua data absensi beserta file gambar fisik berhasil dihapus!'
                : 'Semua data absensi berhasil dihapus (tanpa menghapus file gambar).'
        );
    }

    public function deleteAbsensiByPeriod(Request $request): RedirectResponse
    {
        $month = $request->query('month');
        $year = $request->query('year');
        $withImage = $request->query('with_image', 0);

        if (!$month || !$year) {
            return back()->with('error', 'Bulan dan Tahun wajib dipilih!');
        }

        try {
            // Debug: Cek contoh data asli di DB
            $sample = Absensi::orderBy('waktu', 'desc')->first();
            if ($sample) {
                \Illuminate\Support\Facades\Log::info("DEBUG: Sample data '{$sample->nama}' waktu: '{$sample->waktu}'");
            } else {
                \Illuminate\Support\Facades\Log::info("DEBUG: Table absensis is empty!");
            }

            // Normalisasi bulan dan tahun
            $date = Carbon::parse("1 $month $year");
            $yearStr = $date->year;
            $monthPadded = str_pad($date->month, 2, '0', STR_PAD_LEFT);
            
            $searchPattern = "{$yearStr}-{$monthPadded}-%";
            $query = Absensi::where('waktu', 'LIKE', $searchPattern);
            
            $count = $query->count();
            \Illuminate\Support\Facades\Log::info("DELETE ACTION: Pattern $searchPattern, Found: $count records.");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("DELETE ERROR: " . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }

        if ($count === 0) {
            return back()->with('warning', "Data tidak ditemukan untuk pola: $searchPattern");
        }

        $deletedCount = 0;
        $query->get()->each(function ($absen) use ($withImage, &$deletedCount) {
            if ($withImage == 1 && $absen->foto) {
                $path = public_path($absen->foto);
                if (File::exists($path)) {
                    File::delete($path);
                }
            }
            if ($absen->delete()) {
                $deletedCount++;
            }
        });

        return back()->with(
            'success',
            "Berhasil menghapus $deletedCount data absensi untuk periode $month-$year." . ($withImage == 1 ? " Termasuk file gambar." : "")
        );
    }

    public function makeAdmin($id): RedirectResponse
    {
        $user = User::findOrFail($id);
        $user->is_admin = 1;
        $user->save();

        return back()->with('success', $user->name . ' sekarang diatur menjadi admin.');
    }

    public function deleteStudent($id): JsonResponse
    {
        $student = Student::findOrFail($id);
        $student->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Data Siswa berhasil dihapus permanen!'
        ]);
    }

    public function removeAdmin($id): RedirectResponse
    {
        $user = User::findOrFail($id);
        $user->is_admin = 0;
        $user->save();

        return back()->with('success', $user->name . ' sekarang telah dicabut akses admin-nya.');
    }

    public function deleteUser($id): JsonResponse
    {
        $user = User::findOrFail($id);

        // Hapus absensi yang berhubungan dengan user ini
        $absensis = Absensi::where('user_id', $user->id)->get();
        foreach ($absensis as $absen) {
            if ($absen->foto && File::exists(public_path($absen->foto))) {
                File::delete(public_path($absen->foto));
            }
            $absen->delete();
        }

        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Data Guru/Pengguna dan rekaman absensinya berhasil dihapus.'
        ]);
    }
}
