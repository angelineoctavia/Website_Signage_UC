<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class UploadController extends Controller
{
    // Menampilkan halaman upload
    public function index()
    {
        return view('upload');
    }

    public function store(Request $request)
    {
        // 1. Validasi input dari form
        $validatedData = $request->validate([
            'content_title'   => 'required|string|max:100',
            'start_datetime'  => 'required|date',
            'end_datetime'    => 'required|date|after:start_datetime',
            'category'        => 'required|string', // Pastikan valuenya 'Event' / 'Daily' sesuai DB atau form
            'duration'        => 'required|string|regex:/^\d{2}:\d{2}$/', // Format MM:SS dari frontend
            'file' => 'required|file|mimes:mp4,mov,avi,jpg,jpeg,png|max:51200', 
        ]);

        // 2. Konversi format durasi "MM:SS" dari frontend menjadi total detik (INT) untuk database
        $durationInput = $request->input('duration', '00:00');
        list($mins, $secs) = explode(':', $durationInput);
        $contentDuration = (int)$mins * 60 + (int)$secs;

        // 3. Proses Upload File & Deteksi Ekstensi File
        $filePathUrl = null;
        $contentType = '';
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $contentType = $file->getClientOriginalExtension(); // Mengambil ekstensi file ('mp4', 'png', dll)
            
            $filename = time() . '_' . $file->getClientOriginalName();
            $filePathUrl = $file->storeAs('uploads/contents', $filename, 'public');
        }

        // 4. Simpan data ke database sesuai struktur tabel `contents` kamu
        DB::table('contents')->insert([
            'users_id'              => Auth::id(), // Pastikan tipe data users_id di tabel users & contents klop (varchar/int)
            'content_title'         => $request->input('content_title'),
            'content_file_path_url' => $filePathUrl,
            'content_category'      => $request->input('category'),
            'content_type'          => $contentType,
            'content_duration'      => $contentDuration, // Masuk sebagai angka (detik)
            'content_start_date'    => $request->input('start_datetime'),
            'content_end_date'      => $request->input('end_datetime'),
            'content_status'        => true, // Default Active
            'status_del'            => '0',
        ]);

        return redirect()->back()->with('success', 'Konten berhasil di-upload dan disimpan!');
    }
}