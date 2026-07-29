<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SignageController extends Controller
{
    // Menampilkan halaman utama signage khusus TV (Role 2)
    public function index()
    {
        // Bisa mengoper data awal konfigurasi layar Samsung 24 inch (misal resolusi 1920x1080 portrait/landscape)
        return view('signage.index');
    }

    // API Endpoint untuk mendownload daftar playlist & asset ke penyimpanan lokal TV
    public function getPlaylistData(Request $request)
    {
        // Contoh data playlist yang dikirim dari database admin
        // TV akan membaca JSON ini, lalu otomatis mendownload file medianya ke penyimpanan lokal
        $playlist = [
            'version' => '1.0.2', // Untuk mengecek apakah ada update playlist baru
            'device_target' => 'Samsung Signage 24 Inch',
            'resolution' => [
                'width' => 1080,
                'height' => 1920 // Contoh Portrait (atau 1920x1080 untuk Landscape)
            ],
            'items' => [
                [
                    'id' => 1,
                    'type' => 'image',
                    'url' => asset('storage/signage/slide-1.jpg'),
                    'duration' => 10 // durasi tampil dalam detik
                ],
                [
                    'id' => 2,
                    'type' => 'video',
                    'url' => asset('storage/signage/video-promo.mp4'),
                    'duration' => 30
                ],
                [
                    'id' => 3,
                    'type' => 'image',
                    'url' => asset('storage/signage/slide-2.jpg'),
                    'duration' => 10
                ]
            ]
        ];

        return response()->json($playlist);
    }
}