<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PlaylistController;
use App\Http\Controllers\SignageController;
use App\Http\Controllers\GoogleDriveController;

// Redirect Halaman Utama ke Login
Route::get('/', function () {
    return view('login');
});

// Tampilan Form Auth
Route::get('/login', function () {
    return view('login');
})->name('login');

Route::get('/register', function () {
    return view('register');
});

// Action Process Auth (POST)
Route::post('/register', [AuthController::class, 'register'])->name('register.process');
Route::post('/login', [AuthController::class, 'login'])->name('login.process');

// Tampilan Forgot Password
Route::get('/forgot-password', function () {
    return view('forgot-password');
});

Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot.process');

// Reset Password View
Route::get('/reset-password', function () {
    return view('reset-password');
})->name('password.reset');

Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('reset.password.update');

// Log Out (Bisa pakai GET atau POST sesuai kebutuhan form)
Route::get('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/login');
})->name('logout');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout.post');


// ROUTE KHUSUS ADMIN (Role 1)
Route::middleware(['auth', 'role:1'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Upload
    Route::get('/upload', [UploadController::class, 'index'])->name('upload.index');
    Route::post('/upload', [UploadController::class, 'store'])->name('upload.store');

    // Profile & Password Settings
    Route::get('/account-settings', [ProfileController::class, 'index'])->name('profile.index');
    Route::match(['put', 'post'], '/password/update', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    // Playlist Management
    Route::get('/playlist', [PlaylistController::class, 'index'])->name('playlist.index');
    Route::post('/playlist', [PlaylistController::class, 'store'])->name('playlist.store');

    // Soft Delete & Restore Playlist
    Route::patch('/playlist/{id}/delete', [PlaylistController::class, 'destroy'])->name('playlist.destroy');
    Route::patch('/playlist/{id}/restore', [PlaylistController::class, 'restore'])->name('playlist.restore');

    // Route untuk memproses tombol konfirmasi show ke TV
    Route::post('/dashboard/show/{id}', [DashboardController::class, 'updateSignageStatus'])->name('dashboard.show');

    // Route untuk memperbarui/menayangkan signage status ke TV
    Route::post('/signage/update/{playlistId}', [DashboardController::class, 'updateSignageStatus'])->name('signage.update');
});

// ROUTE KHUSUS TV / SIGNAGE DISPLAY (Role 2)
Route::middleware(['auth', 'role:2'])->group(function () {
    Route::get('/signage-view', [SignageController::class, 'index']);
    Route::get('/api/signage/playlist', [SignageController::class, 'getPlaylistData']);
});

Route::get('/google/login', [GoogleDriveController::class, 'redirectToGoogle'])->name('google.login');
Route::get('/google/callback', [GoogleDriveController::class, 'handleGoogleCallback']);