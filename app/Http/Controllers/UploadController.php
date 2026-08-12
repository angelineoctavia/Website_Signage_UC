<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Http\Controllers\GoogleDriveController;

class UploadController extends Controller
{
    public function index()
    {
        return view('upload');
    }

    public function store(Request $request)
    {
        $request->validate([
            'content_title'  => 'required|string|max:100',
            'category'       => 'required|string',
            'duration'       => 'required|string',
            'content_major_and_department' => 'required|string',
            'file'           => ['required', 'file', 'max:51200', function ($attribute, $value, $fail) {
                $allowed = ['mp4', 'mov', 'avi', 'jpg', 'jpeg', 'png'];
                $ext = strtolower($value->getClientOriginalExtension());
                if (!in_array($ext, $allowed)) {
                    $fail('File harus berformat: ' . implode(', ', $allowed) . '.');
                }
            }],
        ]);

        // Konversi durasi MM:SS ke detik
        $durationInput = $request->input('duration', '00:00');
        if (str_contains($durationInput, ':')) {
            [$mins, $secs] = explode(':', $durationInput);
            $contentDataDuration = (int) $mins * 60 + (int) $secs;
        } else {
            $contentDataDuration = (int) $durationInput;
        }

        $file = $request->file('file');
        $contentType = $file->getClientOriginalExtension();

        $tanggal = date('Y-m-d');
        $cleanTitle = Str::slug($request->input('content_title'), '_');
        $filename = "{$tanggal}_{$cleanTitle}.{$contentType}";

        // --- 1. SIMPAN FILE SECARA FISIK KE LOCAL STORAGE (UNTUK TV) ---
        // File akan masuk ke folder storage/app/public/uploads/
        $localPath = $file->storeAs('uploads', $filename, 'public');

        // --- 2. UPLOAD KE GOOGLE DRIVE (TETAP JALAN SEPERTI BIASA) ---
        try {
            $uploadedFile = GoogleDriveController::uploadFile(
                $file->getRealPath(),
                $filename,
                $file->getMimeType()
            );
        } catch (\Throwable $e) {
            Log::error('Google Drive upload failed: ' . $e->getMessage());

            return redirect()->back()
                ->withErrors(['file' => 'Upload ke Google Drive gagal: ' . $e->getMessage()])
                ->withInput();
        }

        // --- 3. SIMPAN KE DATABASE ---
        // PERHATIAN: Kolom content_file_path_url kita arahkan ke path lokal (uploads/...) 
        // agar TV bisa membacanya via asset('storage/...'), bukan pakai ID Google Drive.
        DB::table('contents')->insert([
            'users_id'                     => Auth::id() ?? 1,
            'content_title'                => $request->input('content_title'),
            'content_file_path_url'        => $localPath, // <-- Path lokal (cth: uploads/2026-08-12_judul.mp4)
            'content_category'             => $request->input('category'),
            'content_type'                 => $contentType,
            'content_duration'             => $contentDataDuration,
            'content_upload_date'          => date('Y-m-d'),
            'content_major_and_department' => $request->input('content_major_and_department'),
            'content_status'               => true,
            'status_del'                   => '0',
        ]);

        return redirect()->route('upload.index')->with('success', 'Konten berhasil disimpan untuk TV & di-backup ke Google Drive!');
    }
}