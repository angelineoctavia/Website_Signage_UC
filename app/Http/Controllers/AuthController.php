<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AuthController extends Controller
{
    // 1. Proses Register (Simpan ke DB)
    public function register(Request $request)
    {
        $request->validate([
            'username'   => 'required|string|max:100',
            'email'      => 'required|email|unique:users,users_email',
            'users_role' => 'required|in:1,2',
            'password' => [
                'required',
                'min:8',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'confirmed'
            ],
        ], [
            'users_role.required' => 'Silakan pilih role akun.',
            'users_role.in' => 'Role akun tidak valid.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.regex' => 'Password harus mengandung kombinasi huruf besar, huruf kecil, dan angka.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.'
        ]);

        $lastUser = User::query()->orderBy('users_id', 'desc')->first();
        if ($lastUser) {
            $lastNum = (int) substr($lastUser->users_id, 1);
            $newId = 'U' . str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newId = 'U0001';
        }

        User::create([
            'users_id'          => $newId,
            'users_name'        => $request->username,
            'users_email'       => $request->email,
            'users_password'    => Hash::make($request->password),
            'users_role'        => $request->users_role,
            'users_acc_created' => \Carbon\Carbon::now()->format('Y-m-d'),
            'status_del'        => '0'
        ]);

        return redirect('/login')->with('success', 'Akun berhasil dibuat! Silakan login.');
    }

    // 2. Proses Login
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::query()
            ->where('users_email', $request->email)
            ->where('status_del', '0')
            ->first();

        if (!$user) {
            return back()->withErrors([
                'email' => 'Account not found. Please check your email or register first.',
            ])->onlyInput('email');
        }

        if (!Auth::attempt(['users_email' => $request->email, 'password' => $request->password])) {
            return back()->withErrors([
                'email' => 'Incorrect password. Please try again.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        $user = Auth::user();
        if ($user->users_role == '1') {
            return redirect()->intended('/dashboard');
        } else {
            return redirect()->intended('/signage-view');
        }
    }

    // 3. Proses Logout
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    // 4. Proses Forgot Password
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::query()
            ->where('users_email', $request->email)
            ->where('status_del', '0')
            ->first();

        if (!$user) {
            return back()->withErrors([
                'email' => 'Account not found. Please check your email or register first.',
            ])->onlyInput('email');
        }

        return back()->with('success', 'Reset link has been sent to your email address!');
    }

    // 5. Proses Reset Password Baru
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => [
                'required',
                'min:8',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'confirmed'
            ],
        ], [
            'password.min' => 'Password minimal 8 karakter.',
            'password.regex' => 'Password harus mengandung kombinasi huruf besar, huruf kecil, dan angka.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.'
        ]);

        $user = User::query()
            ->where('users_email', $request->email)
            ->where('status_del', '0')
            ->first();

        if (!$user) {
            return back()->withErrors(['email' => 'User not found.']);
        }

        $user->update([
            'users_password' => Hash::make($request->password)
        ]);

        return redirect('/login')->with('success', 'Password reset successful! Please log in with your new password.');
    }
}