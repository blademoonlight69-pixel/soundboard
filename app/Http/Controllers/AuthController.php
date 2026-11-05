<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * AuthController — menangani registrasi, login, dan logout.
 *
 * KEAMANAN:
 * - Registrasi publik selalu menghasilkan role 'user' (tidak pernah 'admin')
 * - Password di-hash otomatis oleh Laravel (lihat casts di model User)
 * - Login memeriksa is_active sebelum mengizinkan masuk
 * - Logout menghapus seluruh session dan membuat token CSRF baru
 */
class AuthController extends Controller
{
    // =========================================================================
    // REGISTRASI
    // =========================================================================

    /**
     * Tampilkan halaman form registrasi.
     * Jika sudah login, redirect ke dashboard.
     */
    public function showRegister(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.register');
    }

    /**
     * Proses registrasi akun baru.
     * SELALU membuat akun dengan role 'user' — tidak ada jalan lain untuk
     * mendapatkan role 'admin' melalui form ini.
     */
    public function register(Request $request): RedirectResponse
    {
        // Validasi input di sisi server
        // Validasi di sisi client (HTML5) adalah kenyamanan, bukan keamanan
        $validated = $request->validate([
            'name'     => ['required', 'string', 'min:3', 'max:100'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            // 'password_confirmation' divalidasi otomatis oleh aturan 'confirmed'
        ], [
            // Pesan error dalam Bahasa Indonesia
            'name.required'         => 'Nama wajib diisi.',
            'name.min'              => 'Nama minimal 3 karakter.',
            'name.max'              => 'Nama maksimal 100 karakter.',
            'email.required'        => 'Email wajib diisi.',
            'email.email'           => 'Format email tidak valid.',
            'email.unique'          => 'Email ini sudah digunakan. Silakan gunakan email lain.',
            'password.required'     => 'Password wajib diisi.',
            'password.min'          => 'Password minimal 8 karakter.',
            'password.confirmed'    => 'Konfirmasi password tidak cocok.',
        ]);

        // Buat akun user baru
        // PENTING: 'role' tidak diambil dari request, selalu 'user'
        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => $validated['password'], // Otomatis di-hash oleh cast di model
        ]);

        // Buat baris settings default untuk user baru
        // (volume 80, tema dark, stop all hotkey 'End')
        UserSetting::create([
            'user_id'         => $user->id,
            'master_volume'   => 80,
            'theme'           => 'dark',
            'stop_all_hotkey' => 'End',
        ]);

        // Login otomatis setelah registrasi berhasil
        Auth::login($user);

        return redirect()->route('dashboard')
            ->with('success', 'Selamat datang di MiniSoundboard, ' . $user->name . '! Akun Anda berhasil dibuat.');
    }

    // =========================================================================
    // LOGIN
    // =========================================================================

    /**
     * Tampilkan halaman form login.
     * Jika sudah login, redirect ke dashboard.
     */
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Proses login.
     * Memeriksa kredensial DAN status akun (is_active).
     */
    public function login(Request $request): RedirectResponse
    {
        // Validasi input
        $validated = $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required'    => 'Email wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        // Cari user berdasarkan email
        $user = User::where('email', $validated['email'])->first();

        // Cek apakah akun ada dan password benar
        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Email atau password tidak benar.']);
        }

        // Cek apakah akun masih aktif
        // Ini dicek di sini (bukan hanya di middleware) agar pesan error lebih informatif
        if (! $user->isActive()) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Akun Anda telah dinonaktifkan. Hubungi administrator.']);
        }

        // Login berhasil — buat session baru
        Auth::login($user, $request->boolean('remember'));

        // Regenerasi session ID untuk mencegah session fixation attack
        $request->session()->regenerate();

        // Pastikan user memiliki settings (untuk akun lama sebelum fitur ini dibuat)
        if (! $user->settings) {
            UserSetting::create([
                'user_id'         => $user->id,
                'master_volume'   => 80,
                'theme'           => 'dark',
                'stop_all_hotkey' => 'End',
            ]);
        }

        return redirect()->intended(route('dashboard'));
    }

    // =========================================================================
    // LOGOUT
    // =========================================================================

    /**
     * Proses logout.
     * Menghapus seluruh session dan membuat CSRF token baru.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        // Hapus seluruh data session
        $request->session()->invalidate();

        // Buat CSRF token baru untuk keamanan
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Anda berhasil logout.');
    }
}
