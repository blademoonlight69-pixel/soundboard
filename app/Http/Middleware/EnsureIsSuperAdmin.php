<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware EnsureIsSuperAdmin — memastikan user yang mengakses memiliki role 'super_admin'.
 *
 * Digunakan untuk melindungi fitur-fitur kritis seperti:
 * - Pengelolaan administrator (mengangkat / mencopot admin)
 * - Perubahan konfigurasi sistem master
 * - Audit log sensitif
 */
class EnsureIsSuperAdmin
{
    /**
     * Tangani request yang masuk.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Pastikan user sudah login dan memiliki role super_admin
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Akses ditolak. Fitur ini hanya dapat diakses oleh Super Admin.',
                ], 403);
            }

            return redirect()->route('admin.dashboard')
                ->with('error', 'Akses ditolak. Anda memerlukan hak akses Super Admin untuk mengakses halaman tersebut.');
        }

        return $next($request);
    }
}
