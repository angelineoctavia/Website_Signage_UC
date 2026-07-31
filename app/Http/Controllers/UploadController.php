<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\GoogleDriveController;

class UploadController extends Controller
{
    public function index()
    {
        return view('upload');
    }

    public function store(Request $request)
    {
        // Validasi fleksibel: bisa terima file fisik ATAU URL dari Google Drive Picker
        $request->validate([
            'content_title'         => 'required|string|max:100',
            'start_datetime'        => 'required|date',
            'end_datetime'          => 'required|date|after:start_datetime',
            'category'              => 'required|string',
            'duration'              => 'required|string',
            'content_file_path_url' => 'nullable|string', // Menerima URL dari Google Drive Picker
            'file'                  => 'nullable|file|mimes:mp4,mov,avi,jpg,jpeg,png|max:51200', // File fisik opsional jika pakai picker
        ]);

        // Konversi durasi format MM:SS ke detik
        $durationInput = $request->input('duration', '00:00');
        if (str_contains($durationInput, ':')) {
            list($mins, $secs) = explode(':', $durationInput);
            $contentDataDuration = (int)$mins * 60 + (int)$secs;
        } else {
            $contentDataDuration = (int)$durationInput;
        }

        $filePathUrl = '';
        $contentType = 'unknown';

        // SKENARIO 1: User memilih file lewat Pop-up Google Drive Picker
        if ($request->filled('content_file_path_url')) {
            $filePathUrl = $request->input('content_file_path_url');
            $contentType = 'gdrive'; // Penanda file dari Google Drive Picker
        }
        // SKENARIO 2: User mengupload file fisik via Drag & Drop (Cara Lama)
        elseif ($request->hasFile('file')) {
            $file = $request->file('file');
            $contentType = $file->getClientOriginalExtension();

            $filename = time() . '_' . $file->getClientOriginalName();
            $tempPath = $file->getRealPath();
            $mimeType = $file->getMimeType();

            // Upload ke Google Drive via Backend Controller kamu
            $uploadedFile = GoogleDriveController::uploadFile($tempPath, $filename, $mimeType);
            $filePathUrl = $uploadedFile['link'];
        } else {
            return redirect()->back()->withErrors(['content_file_path_url' => 'Silakan pilih file terlebih dahulu!'])->withInput();
        }

        // Simpan ke database tabel contents
        DB::table('contents')->insert([
            'users_id'              => Auth::id() ?? 1,
            'content_title'         => $request->input('content_title'),
            'content_file_path_url' => $filePathUrl,
            'content_category'      => $request->input('category'),
            'content_type'          => $contentType,
            'content_duration'      => $contentDataDuration,
            'content_start_date'    => $request->input('start_datetime'),
            'content_end_date'      => $request->input('end_datetime'),
            'content_status'        => true,
            'status_del'            => '0',
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);

        return redirect()->back()->with('success', 'Konten berhasil disimpan ke database!');
    }
}
