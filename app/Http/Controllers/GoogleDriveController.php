<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google_Client;
use Google_Service_Drive;
use Google_Service_Drive_DriveFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

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
            Storage::put('google-token.json', json_encode($token));
            return "Berhasil menghubungkan Google Drive! Token sudah tersimpan aman. Kamu sekarang bisa menutup halaman ini.";
        }

        return redirect('/');
    }

    // 3. Fungsi statis untuk mengupload file dari controller lain/signage
    public static function uploadFile($filePath, $fileName, $mimeType)
    {
        $customTempDir = storage_path('app/tmp');
        if (!is_dir($customTempDir)) {
            mkdir($customTempDir, 0755, true);
        }
        ini_set('sys_temp_dir', $customTempDir);
        putenv("TMP={$customTempDir}");
        putenv("TEMP={$customTempDir}");

        $client = new Google_Client();
        $client->setClientId(env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));

        if (!Storage::exists('google-token.json')) {
            throw new \Exception("Google Drive belum dihubungkan! Silakan akses URL /google/login terlebih dahulu.");
        }

        $token = json_decode(Storage::get('google-token.json'), true);
        $client->setAccessToken($token);

        if ($client->isAccessTokenExpired()) {
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                Storage::put('google-token.json', json_encode($client->getAccessToken()));
            } else {
                throw new \Exception("Token Google Drive kedaluwarsa. Silakan hubungkan ulang via /google/login.");
            }
        }

        $service = new Google_Service_Drive($client);
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

    public function streamFile($fileId, $ext = 'mp4')
    {
        $mimeMap = [
            'mp4'  => 'video/mp4',
            'mov'  => 'video/quicktime',
            'avi'  => 'video/x-msvideo',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
        ];
        $mimeType = $mimeMap[strtolower($ext)] ?? 'application/octet-stream';

        // Cek cache lokal dulu — kalau sudah pernah didownload, langsung sajikan dari sini (tanpa hit Google Drive API lagi)
        $cachePath = 'drive-cache/' . $fileId . '.' . $ext;
        if (Storage::exists($cachePath)) {
            $content = Storage::get($cachePath);

            return response($content, 200)
                ->header('Content-Type', $mimeType)
                ->header('Cache-Control', 'public, max-age=31536000')
                ->header('Access-Control-Allow-Origin', '*');
        }

        // Belum ada di cache lokal — ambil dari Google Drive API, lalu simpan buat request berikutnya
        $client = new Google_Client();
        $client->setClientId(env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));

        if (!Storage::exists('google-token.json')) {
            abort(404, 'Google Drive belum terhubung.');
        }

        $token = json_decode(Storage::get('google-token.json'), true);
        $client->setAccessToken($token);

        if ($client->isAccessTokenExpired()) {
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                Storage::put('google-token.json', json_encode($client->getAccessToken()));
            } else {
                abort(404, 'Token Google Drive kedaluwarsa.');
            }
        }

        try {
            $client->setDefer(true);
            $service = new Google_Service_Drive($client);
            $request = $service->files->get($fileId, ['alt' => 'media']);

            $httpResponse = $client->execute($request);
            $content = (string) $httpResponse->getBody();
        } catch (\Throwable $e) {
            Log::error('Google Drive stream failed for file ' . $fileId . ': ' . $e->getMessage());
            abort(404, 'File tidak ditemukan di Google Drive.');
        }

        // Simpan ke cache lokal supaya request berikutnya tidak perlu hit Google Drive API lagi
        Storage::put($cachePath, $content);

        return response($content, 200)
            ->header('Content-Type', $mimeType)
            ->header('Cache-Control', 'public, max-age=31536000')
            ->header('Access-Control-Allow-Origin', '*');
    }
}
