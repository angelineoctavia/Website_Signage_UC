<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Playlist;
use App\Models\Content;
use Illuminate\Support\Facades\DB;

class SignageController extends Controller
{
    // Menampilkan halaman utama signage khusus TV (Role 2)
    public function index()
    {
        return view('signage.index');
    }

    public function getPlaylistData(Request $request)
    {
        try {
            $today = now()->format('Y-m-d');

            $activePlaylist = Playlist::where('status_del', '0')
                ->whereDate('playlist_start_date', '<=', $today)
                ->whereDate('playlist_end_date', '>=', $today)
                ->first();

            if (!$activePlaylist) {
                return response()->json([
                    'version' => 'none-' . $today,
                    'device_target' => 'Samsung Signage 24 Inch',
                    'items' => []
                ], 200);
            }

            $playlistDetails = DB::table('playlist_details')
                ->join('contents', 'playlist_details.contents_id', '=', 'contents.contents_id')
                ->where('playlist_details.playlist_id', $activePlaylist->playlist_id)
                ->orderBy('playlist_details.playlist_order', 'asc')
                ->select('contents.*', 'playlist_details.playlist_order')
                ->get();

            $items = [];
            foreach ($playlistDetails as $detail) {
                $filePath = $detail->content_file_path_url ?? '';
                if (empty($filePath) || $filePath === 'storage' || $filePath === '/storage') {
                    continue;
                }

                $fileUrl = Content::resolveFileUrl($filePath, $detail->content_type ?? null);
                $contentType = strtolower($detail->content_type ?? '');
                $isImage = in_array($contentType, ['image', 'img', 'jpg', 'jpeg', 'png']);

                $items[] = [
                    'id' => $detail->contents_id,
                    'type' => $isImage ? 'image' : 'video',
                    'url' => $fileUrl,
                    'duration' => $detail->content_duration ?? ($isImage ? 10 : 15)
                ];
            }

            // Version sekarang include hash dari content_ids — berubah setiap kali isi playlist diupdate
            $contentHash = md5(implode('-', array_column($items, 'id')));
            $version = 'p' . $activePlaylist->playlist_id . '-' . $today . '-' . substr($contentHash, 0, 8);

            return response()->json([
                'version' => $version,
                'playlist_id' => $activePlaylist->playlist_id,
                'device_target' => 'Samsung Signage 24 Inch',
                'items' => $items
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
                'items' => []
            ], 200);
        }
    }
}
