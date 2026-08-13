<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Student;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\StudentDataImport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class ImportController extends Controller
{
    /**
     * Import Excel siswa (multi-sheet) atau tambah manual.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function import(Request $request): RedirectResponse|JsonResponse
    {
        try {
            // --- 1. IMPORT DARI FILE EXCEL ---
            if ($request->hasFile('excel')) {
                // Validasi agar file yang diunggah harus excel
                $request->validate([
                    'excel' => 'required|file|mimes:xlsx,xls,csv|max:10240'
                ]);

                $file = $request->file('excel');

                Excel::import(new StudentDataImport, $file);

                return redirect()->back()->with('success', 'Import Excel berhasil diproses!');
            }

            // --- 2. TAMBAH SISWA MANUAL ---
            $validated = $request->validate([
                'name'  => 'required|string|max:255',
                'grade' => 'required|string|max:10',
            ]);

            $student = Student::create([
                'name'  => $validated['name'],
                'grade' => $validated['grade'],
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Siswa berhasil ditambahkan secara manual!',
                'data'    => $student
            ], 201);

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'error' => 'Terjadi kesalahan internal: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Terjadi kesalahan internal: ' . $e->getMessage());
        }
    }
}
