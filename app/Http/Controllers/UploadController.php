<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
        $filename = time() . '_' . $file->getClientOriginalName();

        // Upload ke Google Drive
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

        // Simpan ke database — sesuai kolom tabel contents (tidak ada created_at/updated_at)
        DB::table('contents')->insert([
            'users_id'              => Auth::id() ?? 1,
            'content_title'         => $request->input('content_title'),
            'content_file_path_url' => $uploadedFile['file_id'],
            'content_category'      => $request->input('category'),
            'content_type'          => $contentType,
            'content_duration'      => $contentDataDuration,
            'content_start_date'    => $request->input('start_datetime'),
            'content_end_date'      => $request->input('end_datetime'),
            'content_status'        => true,
            'status_del'            => '0',
        ]);

        return redirect()->route('upload.index')->with('success', 'Konten berhasil di-upload ke Google Drive & disimpan ke database!');
    }
}