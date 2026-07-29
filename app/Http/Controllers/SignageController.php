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

            // Lakukan JOIN dari playlist_details ke contents untuk mengambil file path & durasi
            $playlistDetails = DB::table('playlist_details')
                ->join('contents', 'playlist_details.contents_id', '=', 'contents.contents_id')
                ->where('playlist_details.playlist_id', $latestStatus->playlist_id)
                ->orderBy('playlist_details.playlist_order', 'asc')
                ->select('contents.*', 'playlist_details.playlist_order')
                ->get();

            $items = [];
            foreach ($playlistDetails as $detail) {
                $filePath = $detail->content_file_path_url ?? '';

                // Skip jika path kosong
                if (empty($filePath) || $filePath === 'storage' || $filePath === '/storage') {
                    continue;
                }

                $fileUrl = str_starts_with($filePath, 'http://') || str_starts_with($filePath, 'https://')
                    ? $filePath
                    : asset('storage/' . ltrim($filePath, '/'));

                // Tentukan tipe berdasarkan kolom content_type atau ekstensi file
                $contentType = strtolower($detail->content_type ?? '');
                $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                $isImage = in_array($contentType, ['image', 'img', 'jpg', 'jpeg', 'png']) || in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);

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