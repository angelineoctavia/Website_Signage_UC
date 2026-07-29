<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Cek apakah user sudah login
        if (!Auth::check()) {
            return redirect('/login');
        }

        $user = Auth::user();

        // Cek apakah users_role user termasuk dalam role yang diizinkan
        if (in_array((string)$user->users_role, $roles)) {
            return $next($request);
        }

        // Jika salah role, arahkan ke halaman yang sesuai atau dashboard masing-masing
        if ($user->users_role == '1') {
            return redirect('/dashboard')->with('error', 'Akses ditolak.');
        } else {
            return redirect('/signage-view')->with('error', 'Akses ditolak.');
        }
    }
}