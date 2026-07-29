<?php

namespace App\Http\Controllers;

use App\Models\Playlist;
use App\Models\SignageStatus;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $fullName = $user->users_name ?? 'Admin';
        $firstName = explode(' ', trim($fullName))[0];

        $playlists = Playlist::with(['details.content.user'])
            ->where('status_del', '0')
            ->get();

        $trashedPlaylists = Playlist::with(['details.content'])
            ->where('status_del', '1')
            ->get();

        // INI YANG DICARI SAMA VIEW-NYA (Baris 85)
        $totalContent = \App\Models\Content::query()->where('status_del', '0')->count();
        $activePlaylists = $playlists->count();
        $averagePlaytime = \App\Models\Content::query()->where('status_del', '0')->average('content_duration') ?? 0;
        $averagePlaytime = round($averagePlaytime, 1);

        $playlistsData = Playlist::with(['details.content'])
            ->where('status_del', '0')
            ->get()
            ->flatMap(function ($playlist) {
                return $playlist->details->map(function ($detail) use ($playlist) {
                    return (object)[
                        'playlist_id' => $playlist->playlist_id,
                        'playlist_date' => $playlist->playlist_date,
                        'playlist_order' => $detail->playlist_order,
                        'content_title' => $detail->content->content_title ?? '-',
                        'content_duration' => $detail->content->content_duration ?? 0,
                        'content_file_path_url' => $detail->content->content_file_path_url ?? '',
                    ];
                });
            });

        $latestSignage = SignageStatus::with(['playlist', 'updatedBy'])
            ->orderBy('status_updated_at', 'desc')
            ->first();

        // PASTIKAN SEMUA VARIABEL INI DI-COMPACT
        return view('dashboard', compact(
            'playlists',
            'trashedPlaylists',
            'playlistsData',
            'latestSignage',
            'firstName',
            'totalContent',
            'activePlaylists',
            'averagePlaytime'
        ));
    }

    public function updateSignageStatus(Request $request, $id)
    {
        // Simpan status signage baru ke database
        SignageStatus::create([
            'playlist_id' => $id,
            'users_id' => Auth::user()->users_id,         // User yang sedang login
            'status_updated_by' => Auth::user()->users_id, // ID admin yang memencet tombol
            'status_updated_at' => Carbon::now('Asia/Jakarta'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Playlist berhasil ditayangkan ke layar TV Signage!'
        ]);
    }
}
