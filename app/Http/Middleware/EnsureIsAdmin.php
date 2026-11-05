<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware EnsureIsAdmin — memastikan user yang mengakses route adalah Admin.
 *
 * Route yang dilindungi middleware ini hanya bisa diakses jika:
 * 1. User sudah login (dicek oleh middleware 'auth' yang dipasang sebelumnya)
 * 2. User memiliki role 'admin'
 *
 * Jika bukan admin, diarahkan ke halaman utama dengan pesan error.
 */
class EnsureIsAdmin
{
    /**
     * Tangani request yang masuk.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Pastikan user sudah login dan memiliki role admin
        if (! $request->user() || ! $request->user()->isAdmin()) {
            // Jika request via AJAX/API, kembalikan respons JSON
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Akses ditolak. Halaman ini hanya untuk admin.',
                ], 403);
            }

            // Redirect dengan pesan error
            return redirect()->route('dashboard')
                ->with('error', 'Akses ditolak. Anda tidak memiliki izin untuk mengakses halaman tersebut.');
        }

        return $next($request);
    }
}
