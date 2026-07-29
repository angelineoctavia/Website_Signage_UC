<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    // Menampilkan halaman profil & data user
    public function index()
    {
        $user = Auth::user();
        return view('profile', compact('user'));
    }

    public function updatePassword(Request $request)
    {
        // Validasi input dari form
        $request->validate([
            'current_password' => ['required'],
            'new_password' => ['required', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'Password lama wajib diisi.',
            'new_password.required' => 'Password baru wajib diisi.',
            'new_password.min' => 'Password baru minimal harus 8 karakter.',
            'new_password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        $user = Auth::user();

        // Cek apakah password lama yang diketik sesuai dengan database
        if (!Hash::check($request->current_password, $user->users_password)) {
            return back()->withErrors([
                'current_password' => 'Password lama yang Anda masukkan salah!'
            ])->withInput();
        }

        // Cek apakah password baru sama dengan password lama
        if (Hash::check($request->new_password, $user->users_password)) {
            return back()->withErrors([
                'new_password' => 'Password baru tidak boleh sama dengan password lama Anda.'
            ])->withInput();
        }

        // Simpan password baru ke database
        $user->users_password = Hash::make($request->new_password);
        $user->save();

        return back()->with('success', 'Password berhasil diperbarui!');
    }
}