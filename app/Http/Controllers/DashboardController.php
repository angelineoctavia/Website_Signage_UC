<?php

namespace App\Http\Controllers;

use App\Models\Playlist;
use App\Models\SignageStatus;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
                        'content_type' => $detail->content->content_type ?? '',
                    ];
                });
            });

        $latestSignage = SignageStatus::with(['playlist', 'updatedBy'])
            ->orderBy('status_updated_at', 'desc')
            ->first();

        // 1. Ambil info Playlist yang SEDANG TAYANG (Aktif) terakhir lengkap dengan nama usernya
        $currentSignage = DB::table('signage_status')
            ->join('playlists', 'signage_status.playlist_id', '=', 'playlists.playlist_id')
            ->join('users', 'signage_status.status_updated_by', '=', 'users.users_id')
            ->orderBy('signage_status.status_updated_at', 'desc')
            ->select(
                'signage_status.*',
                'playlists.playlist_date',
                'users.users_name as updated_by_name'
            )
            ->first();

        $allSignageHistories = DB::table('signage_status')
            ->join('users', 'signage_status.status_updated_by', '=', 'users.users_id')
            ->orderBy('signage_status.status_updated_at', 'desc') // Urutkan dari yang terbaru
            ->select(
                'signage_status.*',
                'users.users_name as status_updated_by' // Kita override agar langsung berisi nama user, atau biarkan ID-nya
            )
            ->get();

        // 2. Ambil daftar semua konten yang di-upload lengkap dengan nama pengunggahnya
        $allContents = DB::table('contents')
            ->join('users', 'contents.users_id', '=', 'users.users_id')
            ->where('contents.status_del', '0')
            ->select('contents.*', 'users.users_name')
            ->get();

        // PASTIKAN SEMUA VARIABEL INI DI-COMPACT
        return view('dashboard', compact(
            'playlists',
            'trashedPlaylists',
            'playlistsData',
            'latestSignage',
            'currentSignage',
            'allSignageHistories',
            'allContents',
            'firstName',
            'totalContent',
            'activePlaylists',
            'averagePlaytime'
        ));
    }

    public function updateSignageStatus(Request $request, $id)
    {
        SignageStatus::create([
            'playlist_id' => $id,
            'users_id' => Auth::user()->users_id,
            'status_updated_by' => Auth::user()->users_id,
            'status_updated_at' => Carbon::now('Asia/Jakarta'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Playlist berhasil ditayangkan ke layar TV Signage!'
        ]);
    }
}
