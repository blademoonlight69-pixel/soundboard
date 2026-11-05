<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Kecualikan endpoint API Desktop Companion dari verifikasi CSRF token
        $middleware->validateCsrfTokens(except: [
            'api/desktop/*',
        ]);

        // Daftarkan alias untuk middleware kustom agar bisa digunakan di route
        // Penggunaan: Route::middleware(['auth', 'active', 'admin'])->group(...)
        $middleware->alias([
            'super_admin' => \App\Http\Middleware\EnsureIsSuperAdmin::class,
            'admin'       => \App\Http\Middleware\EnsureIsAdmin::class,
            'active'      => \App\Http\Middleware\EnsureIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
