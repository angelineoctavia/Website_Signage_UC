<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Playlist;
use App\Models\PlaylistDetail;

class PlaylistController extends Controller
{
    public function index()
    {
        $contents = DB::table('contents')->where('status_del', '0')->get();

        foreach ($contents as $content) {
            $path = $content->content_file_path_url ?? '';
            if (!filter_var($path, FILTER_VALIDATE_URL)) {
                $content->full_url = asset('storage/' . ltrim($path, '/'));
            } else {
                $content->full_url = $path;
            }

            // Deteksi jenis file (gambar vs video)
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $content->is_image = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);

            // Default durasi 5 detik untuk gambar jika kosong, atau pakai durasi database
            $content->duration_seconds = $content->content_duration ?? ($content->is_image ? 5 : 10);
        }

        return view('playlist', compact('contents'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'playlist_date' => ['required', 'date', 'after_or_equal:today'],
            'contents' => ['required', 'array', 'min:1'],
            'contents.*' => ['exists:contents,contents_id']
        ]);

        // 1. Ambil data konten yang dipilih untuk menghitung total durasi
        $selectedContents = DB::table('contents')->whereIn('contents_id', $request->contents)->get();

        $totalDuration = $selectedContents->sum(function ($content) {
            $extension = strtolower(pathinfo($content->content_file_path_url, PATHINFO_EXTENSION));
            $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
            return $content->content_duration ?? ($isImage ? 5 : 10);
        });

        // 2. Simpan Header Playlist ke tabel 'playlists'
        $playlist = Playlist::create([
            'playlist_date' => $request->playlist_date,
            'playlist_duration' => $totalDuration,
            'status_del' => '0'
        ]);

        // 3. Simpan Detail Playlist ke tabel 'playlist_details' beserta urutannya
        foreach ($request->contents as $index => $contentId) {
            PlaylistDetail::create([
                'playlist_id' => $playlist->playlist_id,
                'contents_id' => $contentId,
                'playlist_order' => $index + 1
            ]);
        }

        return redirect()->route('playlist.index')->with('success', 'Playlist berhasil disimpan ke database!');
    }

    // 1. Soft Delete Playlist
    public function destroy($id)
    {
        Playlist::query()->where('playlist_id', $id)->update(['status_del' => '1']);
        return redirect()->route('dashboard')->with('success', 'Playlist berhasil dipindahkan ke sampah.');
    }
    
    // 3. Restore Playlist
    public function restore($id)
    {
        Playlist::query()->where('playlist_id', $id)->update(['status_del' => '0']);
        return redirect()->route('dashboard')->with('success', 'Playlist berhasil dipulihkan!');
    }
}
