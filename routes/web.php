<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — MiniSoundboard Gaming Edition
|--------------------------------------------------------------------------
|
| Struktur route:
|
| [Publik]
|   GET  /              → Halaman selamat datang / redirect ke dashboard
|   GET  /login         → Form login
|   POST /login         → Proses login
|   GET  /register      → Form registrasi
|   POST /register      → Proses registrasi
|
| [Autentikasi diperlukan — middleware: auth, active]
|   POST /logout        → Proses logout
|   GET  /dashboard     → Dashboard utama user/admin
|
| [Hanya Admin — middleware: auth, active, admin]
|   GET  /admin/*       → Panel admin
|
| Route-route untuk fitur soundboard akan ditambahkan di Tahap 3.
*/

// ============================================================
// ROUTE PUBLIK — Tidak memerlukan login
// ============================================================

// Desktop Companion API (Mendukung Login & Sync via HMAC Token)
Route::post('/api/desktop/login', [\App\Http\Controllers\HotkeyController::class, 'desktopLogin'])->name('api.desktop.login');
Route::get('/api/desktop/hotkeys', [\App\Http\Controllers\HotkeyController::class, 'apiDesktopSync'])->name('api.desktop.hotkeys');
Route::get('/api/desktop/sounds/{sound}/play', [\App\Http\Controllers\HotkeyController::class, 'playDesktopSound'])->name('api.desktop.sounds.play');
// Halaman utama: redirect ke dashboard jika sudah login, atau ke login jika belum
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

// Autentikasi
Route::middleware('guest')->group(function () {
    // Halaman login
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');

    // Proses login
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');

    // Halaman registrasi
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');

    // Proses registrasi (selalu menghasilkan role 'user')
    Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
});

// ============================================================
// ROUTE YANG MEMERLUKAN LOGIN
// Middleware 'auth': cek apakah sudah login (bawaan Laravel)
// Middleware 'active': cek apakah akun masih aktif (kustom kita)
// ============================================================

Route::middleware(['auth', 'active'])->group(function () {

    // Logout (POST untuk mencegah CSRF)
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard utama — tampilkan berbeda untuk admin dan user
    // (Konten dashboard ditentukan di dalam view berdasarkan auth()->user()->isAdmin())
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // ──────────────────────────────────────────────────────────
    // ROUTE SOUNDBOARD & AUDIO
    // ──────────────────────────────────────────────────────────

    // 1. Katalog Suara Publik
    Route::get('/sounds/catalog', [\App\Http\Controllers\SoundController::class, 'catalog'])->name('sounds.catalog');

    // 2. Koleksi Suara Milik Saya & Manajemen Suara
    Route::get('/my-sounds', [\App\Http\Controllers\SoundController::class, 'mySounds'])->name('my-sounds');
    Route::post('/sounds', [\App\Http\Controllers\SoundController::class, 'store'])->name('sounds.store');
    Route::patch('/sounds/{sound}', [\App\Http\Controllers\SoundController::class, 'update'])->name('sounds.update');
    Route::delete('/sounds/{sound}', [\App\Http\Controllers\SoundController::class, 'destroy'])->name('sounds.destroy');

    // Streaming Audio dengan Validasi Otorisasi Server
    Route::get('/sounds/{sound}/play', [\App\Http\Controllers\SoundController::class, 'play'])->name('sounds.play');

    // 3. Suara Favorit
    Route::get('/favorites', [\App\Http\Controllers\FavoriteController::class, 'index'])->name('favorites');
    Route::post('/sounds/{sound}/favorite', [\App\Http\Controllers\FavoriteController::class, 'toggle'])->name('sounds.favorite');

    // 4. Hotkey Manager & Stop All Settings
    Route::get('/hotkeys', [\App\Http\Controllers\HotkeyController::class, 'index'])->name('hotkeys');
    Route::post('/hotkeys', [\App\Http\Controllers\HotkeyController::class, 'store'])->name('hotkeys.store');
    Route::delete('/hotkeys/{hotkey}', [\App\Http\Controllers\HotkeyController::class, 'destroy'])->name('hotkeys.destroy');
    Route::post('/hotkeys/stop-all', [\App\Http\Controllers\HotkeyController::class, 'updateStopAll'])->name('hotkeys.stop-all.update');
    Route::post('/hotkeys/stop-all/reset', [\App\Http\Controllers\HotkeyController::class, 'resetStopAll'])->name('hotkeys.stop-all.reset');



    // ──────────────────────────────────────────────────────────
    // ROUTE ADMIN — Hanya untuk role admin
    // Middleware 'admin': cek apakah role = 'admin' (kustom kita)
    // ──────────────────────────────────────────────────────────
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {

        // Dashboard admin
        Route::get('/', function () {
            return view('admin.dashboard');
        })->name('dashboard');

        // Manajemen Pengguna & Administrator
        Route::get('/users', [\App\Http\Controllers\Admin\AdminUserController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}/status', [\App\Http\Controllers\Admin\AdminUserController::class, 'toggleStatus'])->name('users.status');

        // Khusus Super Admin: Mengubah role pengguna / mengangkat admin
        Route::patch('/users/{user}/role', [\App\Http\Controllers\Admin\AdminUserController::class, 'updateRole'])
            ->middleware('super_admin')
            ->name('users.role');
    });
});
