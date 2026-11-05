<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware EnsureIsActive — memastikan akun user masih aktif.
 *
 * Admin bisa menonaktifkan akun user dengan is_active = false.
 * User yang dinonaktifkan akan otomatis dilogout dan tidak bisa login kembali
 * sampai adminmengaktifkan kembali akunnya.
 */
class EnsureIsActive
{
    /**
     * Tangani request yang masuk.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Jika user sudah login tapi akunnya tidak aktif
        if ($request->user() && ! $request->user()->isActive()) {
            // Logout user
            Auth::logout();

            // Hapus session dan buat token CSRF baru
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Redirect ke halaman login dengan pesan
            return redirect()->route('login')
                ->with('error', 'Akun Anda telah dinonaktifkan. Hubungi administrator untuk informasi lebih lanjut.');
        }

        return $next($request);
    }
}
