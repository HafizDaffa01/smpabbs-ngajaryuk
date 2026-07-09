<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class FileManagerController extends Controller
{
    /**
     * Resolve and validate the target path securely.
     */
    private function resolvePath(?string $path = ''): string
    {
        $basePath = public_path('uploads');
        
        if (!File::exists($basePath)) {
            File::makeDirectory($basePath, 0755, true);
        }

        if (!$path) {
            return $basePath;
        }

        $resolved = realpath($basePath . DIRECTORY_SEPARATOR . $path);

        if ($resolved === false || !str_starts_with($resolved, $basePath)) {
            return $basePath;
        }

        return $resolved;
    }

    /**
     * Display the file manager explorer view.
     */
    public function index(Request $request)
    {
        $path = $request->query('path', '');
        $fullPath = $this->resolvePath($path);

        if (!File::exists($fullPath)) {
            return redirect()->route('explorer.index')->with('error', 'Direktori tidak ditemukan.');
        }

        $files = File::files($fullPath);
        $dirs = File::directories($fullPath);

        return view('explorer', compact('files', 'dirs', 'path'));
    }

    /**
     * Create a new folder.
     */
    public function createFolder(Request $request)
    {
        $request->validate([
            'name' => 'required|string|regex:/^[a-zA-Z0-9_\-\.]+$/'
        ], [
            'name.regex' => 'Nama folder hanya boleh mengandung huruf, angka, strip, dan underscore.'
        ]);
        
        $fullPath = $this->resolvePath($request->input('path', ''));
        $folderPath = $fullPath . DIRECTORY_SEPARATOR . $request->name;

        if (File::exists($folderPath)) {
            return back()->with('error', 'Folder dengan nama tersebut sudah ada.');
        }

        try {
            File::makeDirectory($folderPath);
            return back()->with('success', 'Folder berhasil dibuat.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membuat folder: ' . $e->getMessage());
        }
    }

    /**
     * Upload a file.
     */
    public function uploadFile(Request $request)
    {
        $request->validate([
            'file' => 'required|file'
        ]);
        
        $fullPath = $this->resolvePath($request->input('path', ''));
        $file = $request->file('file');
        
        try {
            $fileName = $file->getClientOriginalName();
            
            // Periksa jika file sudah ada (opsional, ditiadakan agar overwrite atau ganti nama opsional, tapi default move akan overwrite)
            $file->move($fullPath, $fileName);
            
            return back()->with('success', "File berhasil diunggah.");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengunggah file: ' . $e->getMessage());
        }
    }

    /**
     * Rename a file or folder.
     */
    public function rename(Request $request)
    {
        $request->validate([
            'old_name' => 'required|string',
            'new_name' => 'required|string|regex:/^[a-zA-Z0-9_\-\.]+$/',
            'type'     => 'required|in:file,folder'
        ]);

        $fullPath = $this->resolvePath($request->input('path', ''));
        $oldPath = $fullPath . DIRECTORY_SEPARATOR . $request->old_name;
        $newPath = $fullPath . DIRECTORY_SEPARATOR . $request->new_name;

        if (!File::exists($oldPath)) {
            return back()->with('error', 'File/Folder yang ingin diubah tidak ditemukan.');
        }

        if (File::exists($newPath)) {
            return back()->with('error', 'Nama tujuan sudah digunakan.');
        }

        try {
            File::move($oldPath, $newPath);
            return back()->with('success', 'Berhasil mengubah nama.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengubah nama: ' . $e->getMessage());
        }
    }

    /**
     * Delete a file.
     */
    public function deleteFile(Request $request)
    {
        $request->validate(['name' => 'required|string']);
        
        $fullPath = $this->resolvePath($request->input('path', ''));
        $filePath = $fullPath . DIRECTORY_SEPARATOR . $request->name;

        if (!File::exists($filePath) || !File::isFile($filePath)) {
            return back()->with('error', 'File tidak ditemukan.');
        }

        try {
            File::delete($filePath);
            return back()->with('success', 'File berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus file: ' . $e->getMessage());
        }
    }

    /**
     * Delete a folder.
     */
    public function deleteFolder(Request $request)
    {
        $request->validate(['name' => 'required|string']);
        
        $fullPath = $this->resolvePath($request->input('path', ''));
        $folderPath = $fullPath . DIRECTORY_SEPARATOR . $request->name;

        if (!File::exists($folderPath) || !File::isDirectory($folderPath)) {
            return back()->with('error', 'Folder tidak ditemukan.');
        }

        try {
            File::deleteDirectory($folderPath);
            return back()->with('success', 'Folder beserta isinya berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus folder: ' . $e->getMessage());
        }
    }
}
