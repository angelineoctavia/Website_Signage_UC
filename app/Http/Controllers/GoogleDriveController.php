<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google_Client;
use Google_Service_Drive;
use Google_Service_Drive_DriveFile;
use Illuminate\Support\Facades\Storage;

class GoogleDriveController extends Controller
{
    private function getClient()
    {
        $client = new Google_Client();
        $client->setClientId(env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $client->setRedirectUri(env('GOOGLE_REDIRECT_URI'));
        $client->addScope(Google_Service_Drive::DRIVE_FILE);
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');
        
        return $client;
    }

    // 1. Membuka halaman login Google (Akses SEKALI SAJA di browser untuk menghubungkan Google Drive kampus)
    public function redirectToGoogle()
    {
        $client = $this->getClient();
        return redirect()->away($client->createAuthUrl());
    }

    // 2. Menangkap token setelah login sukses dan menyimpannya permanen ke storage
    public function handleGoogleCallback(Request $request)
    {
        $client = $this->getClient();
        
        if ($request->has('code')) {
            $token = $client->fetchAccessTokenWithAuthCode($request->get('code'));
            
            // Simpan token ke storage app (storage/app/google-token.json)
            Storage::put('google-token.json', json_encode($token));
            
            return "Berhasil menghubungkan Google Drive! Token sudah tersimpan aman. Kamu sekarang bisa menutup halaman ini.";
        }

        return redirect('/');
    }

    // 3. Fungsi statis untuk mengupload file dari controller lain/signage
    public static function uploadFile($filePath, $fileName, $mimeType)
    {
        $client = new Google_Client();
        $client->setClientId(env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));

        // Cek apakah file token sudah ada
        if (!Storage::exists('google-token.json')) {
            throw new \Exception("Google Drive belum dihubungkan! Silakan akses URL /google/login terlebih dahulu.");
        }

        $token = json_decode(Storage::get('google-token.json'), true);
        $client->setAccessToken($token);

        // Refresh token otomatis jika kedaluwarsa
        if ($client->isAccessTokenExpired()) {
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                Storage::put('google-token.json', json_encode($client->getAccessToken()));
            } else {
                throw new \Exception("Token Google Drive kedaluwarsa. Silakan hubungkan ulang via /google/login.");
            }
        }

        $service = new Google_Service_Drive($client);

        // Ambil Folder ID dari file .env secara dinamis
        $folderId = env('GOOGLE_DRIVE_FOLDER_ID'); 

        $fileMetadata = new Google_Service_Drive_DriveFile();
        $fileMetadata->setName($fileName);
        
        if (!empty($folderId)) {
            $fileMetadata->setParents([$folderId]);
        }

        $content = file_get_contents($filePath);

        $file = $service->files->create($fileMetadata, [
            'data' => $content,
            'mimeType' => $mimeType,
            'uploadType' => 'multipart',
            'fields' => 'id, webViewLink, webContentLink'
        ]);

        return [
            'file_id' => $file->id,
            'link' => $file->webViewLink
        ];
    }
}