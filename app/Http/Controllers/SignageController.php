<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SignageStatus;
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
            $latestStatus = SignageStatus::orderBy('status_updated_at', 'desc')
                ->orderBy('status_id', 'desc')
                ->first();

            if (!$latestStatus) {
                return response()->json(['items' => []], 200);
            }

            $playlistDetails = DB::table('playlist_details')
                ->join('contents', 'playlist_details.contents_id', '=', 'contents.contents_id')
                ->where('playlist_details.playlist_id', $latestStatus->playlist_id)
                ->orderBy('playlist_details.playlist_order', 'asc')
                ->select('contents.*', 'playlist_details.playlist_order')
                ->get();

            $items = [];
            foreach ($playlistDetails as $detail) {
                $filePath = $detail->content_file_path_url ?? '';

                if (empty($filePath) || $filePath === 'storage' || $filePath === '/storage') {
                    continue;
                }

                // Pakai resolver terpusat — otomatis handle: file ID Drive baru, URL Drive lama, atau path lokal lama
                $fileUrl = \App\Models\Content::resolveFileUrl($filePath, $detail->content_type ?? null);

                $contentType = strtolower($detail->content_type ?? '');
                $isImage = in_array($contentType, ['image', 'img', 'jpg', 'jpeg', 'png']);

                $items[] = [
                    'id' => $detail->contents_id,
                    'type' => $isImage ? 'image' : 'video',
                    'url' => $fileUrl,
                    'duration' => $detail->content_duration ?? ($isImage ? 10 : 15)
                ];
            }

            return response()->json([
                'version' => '1.' . $latestStatus->status_id,
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
