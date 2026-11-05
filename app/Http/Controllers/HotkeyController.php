<?php

namespace App\Http\Controllers;

use App\Models\Hotkey;
use App\Models\Sound;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

/**
 * HotkeyController — mengelola pemetaan tombol keyboard ke suara tertentu per pengguna.
 */
class HotkeyController extends Controller
{
    /**
     * Tampilkan halaman Hotkey Manager (/hotkeys).
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // Ambil daftar hotkey milik user yang sedang login
        $hotkeys = $user->hotkeys()
            ->with('sound')
            ->orderBy('key_combo')
            ->get();

        // Ambil daftar suara yang bisa dipilih untuk hotkey:
        // Suara publik ATAU suara milik user sendiri
        $availableSounds = Sound::query()
            ->where(function ($q) use ($user) {
                $q->where('status', 'public')
                  ->orWhere('user_id', $user->id);
            })
            ->orderBy('title')
            ->get();

        // Ambil pengaturan Stop All milik user (default: 'End')
        $stopAllHotkey = $user->settings?->stop_all_hotkey ?? 'End';

        return view('hotkeys.index', compact('hotkeys', 'availableSounds', 'stopAllHotkey'));
    }

    /**
     * Perbarui tombol Stop All hotkey khusus untuk user ini.
     * Route: POST /hotkeys/stop-all
     */
    public function updateStopAll(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'stop_all_hotkey' => ['required', 'string', 'max:30'],
        ], [
            'stop_all_hotkey.required' => 'Tombol Stop All wajib ditentukan.',
            'stop_all_hotkey.max'      => 'Kombinasi tombol terlalu panjang.',
        ]);

        $newCombo = trim($validated['stop_all_hotkey']);

        // Cegah konflik: cek apakah tombol ini sudah dipakai untuk salah satu suara user
        $conflictSound = Hotkey::where('user_id', $user->id)
            ->whereRaw('UPPER(key_combo) = ?', [strtoupper($newCombo)])
            ->with('sound')
            ->first();

        if ($conflictSound) {
            $soundName = $conflictSound->sound?->title ?? 'Suara Lain';
            return back()->with('error', "Tombol [{$newCombo}] sudah digunakan untuk suara '{$soundName}'. Silakan gunakan tombol lain atau ubah hotkey suara tersebut.");
        }

        // Simpan ke user_settings
        $settings = $user->settings()->firstOrCreate([], [
    'master_volume'   => 80,
    'theme'           => 'dark',
    'stop_all_hotkey' => 'End',
]);

        $settings->update([
            'stop_all_hotkey' => $newCombo,
        ]);

        return back()->with('success', "Hotkey Stop All berhasil diubah menjadi [{$newCombo}]! ⏹️");
    }

    /**
     * Kembalikan tombol Stop All ke default sistem 'End'.
     * Route: POST /hotkeys/stop-all/reset
     */
    public function resetStopAll(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Cek apakah ada konflik dengan tombol 'End'
        $conflictSound = Hotkey::where('user_id', $user->id)
            ->whereRaw('UPPER(key_combo) = ?', ['END'])
            ->with('sound')
            ->first();

        if ($conflictSound) {
            $soundName = $conflictSound->sound?->title ?? 'Suara Lain';
            return back()->with('error', "Tombol default [End] saat ini terhubung ke efek suara '{$soundName}'. Ganti atau hapus hotkey suara tersebut terlebih dahulu untuk mengembalikan Stop All ke [End].");
        }

        $settings = $user->settings()->firstOrCreate([], [
    'master_volume'   => 80,
    'theme'           => 'dark',
    'stop_all_hotkey' => 'End',
]);

        $settings->update([
            'stop_all_hotkey' => 'End',
        ]);

        return back()->with('success', "Hotkey Stop All berhasil dikembalikan ke tombol default sistem: [End]! ⏹️");
    }

    /**
     * Daftarkan pemetaan hotkey baru.
     * Route: POST /hotkeys
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'sound_id'  => ['required', 'exists:sounds,id'],
            'key_combo' => ['required', 'string', 'max:30'],
            'label'     => ['nullable', 'string', 'max:100'],
        ], [
            'sound_id.required'  => 'Pilih suara yang ingin dipetakan.',
            'sound_id.exists'    => 'Suara yang dipilih tidak ditemukan.',
            'key_combo.required' => 'Tombol kombinasi wajib diisi.',
            'key_combo.max'      => 'Kombinasi tombol terlalu panjang.',
        ]);

        // Standarisasi key combo (contoh: "f1" -> "F1", "ctrl+1" -> "Ctrl+1")
        $keyCombo = trim($validated['key_combo']);

        // Cegah konflik dengan hotkey Stop All milik user
        $stopAllHotkey = strtoupper($user->settings?->stop_all_hotkey ?? 'End');
        if (strtoupper($keyCombo) === $stopAllHotkey) {
            return back()->withInput()->with('error', "Tombol [{$keyCombo}] saat ini digunakan sebagai Hotkey Stop All sistem. Silakan pilih tombol kombinasi lain.");
        }

        // Verifikasi izin akses ke suara yang dipilih
        $sound = Sound::findOrFail($validated['sound_id']);
        $canAccess = $sound->isPublic() || $sound->user_id === $user->id || $user->isAdmin();
        if (! $canAccess) {
            abort(403, 'Anda tidak dapat memetakan hotkey ke suara privat milik orang lain.');
        }

        // Cek apakah key_combo sudah digunakan oleh user ini
        $existing = Hotkey::where('user_id', $user->id)
            ->where('key_combo', $keyCombo)
            ->first();

        if ($existing) {
            // Update pemetaan suara yang ada
            $existing->update([
                'sound_id' => $sound->id,
                'label' => ($validated['label'] ?? null) ?: $sound->title,
            ]);
            return back()->with('success', "Hotkey [{$keyCombo}] berhasil dialihkan ke '{$sound->title}'.");
        }

        // Buat hotkey baru
        Hotkey::create([
            'user_id'   => $user->id,
            'sound_id'  => $sound->id,
            'key_combo' => $keyCombo,
            'label' => ($validated['label'] ?? null) ?: $sound->title,
        ]);

        return back()->with('success', "Hotkey [{$keyCombo}] untuk '{$sound->title}' berhasil diaktifkan! ⌨️");
    }

    /**
     * Endpoint Login untuk Aplikasi Desktop Windows Companion.
     * Route: POST /api/desktop/login
     */
    public function desktopLogin(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Email atau kata sandi tidak cocok.',
            ], 401);
        }

        if (! $user->isActive()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Akun gamer Anda telah dinonaktifkan.',
            ], 403);
        }

        $syncToken = hash_hmac('sha256', $user->id . '|' . $user->email . '|' . $user->created_at, config('app.key'));
        $stopAllHotkey = $user->settings?->stop_all_hotkey ?? 'End';
        $masterVolume = $user->settings?->master_volume ?? 80;

        $hotkeys = $user->hotkeys()
            ->with('sound')
            ->get()
            ->filter(fn($hk) => $hk->sound !== null)
            ->map(function ($hk) {
                return [
                    'id'          => $hk->id,
                    'key_combo'   => $hk->key_combo,
                    'label'       => $hk->display_label,
                    'sound_id'    => $hk->sound_id,
                    'sound_title' => $hk->sound->title,
                    'play_url' => route('api.desktop.sounds.play', $hk->sound),
                ];
            })->values();

        return response()->json([
            'status'     => 'success',
            'sync_token' => $syncToken,
            'user'       => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
            'settings'   => [
                'stop_all_hotkey' => $stopAllHotkey,
                'master_volume'   => $masterVolume,
            ],
            'hotkeys'    => $hotkeys,
        ]);
    }

    /**
 * Validasi identitas Desktop Companion menggunakan HMAC Token.
 */
private function authenticateDesktop(Request $request): ?User
{
    $email = $request->header('X-User-Email');
    $token = $request->header('X-Sync-Token');

    if (! $email || ! $token) {
        return null;
    }

    $user = User::where('email', $email)->first();

    if (! $user || ! $user->isActive()) {
        return null;
    }

    $expected = hash_hmac(
        'sha256',
        $user->id . '|' . $user->email . '|' . $user->created_at,
        config('app.key')
    );

    return hash_equals($expected, (string) $token) ? $user : null;
}

/**
 * Streaming audio khusus Desktop Companion.
 * Route: GET /api/desktop/sounds/{sound}/play
 */
public function playDesktopSound(Request $request, Sound $sound): Response|StreamedResponse
{
    $user = $this->authenticateDesktop($request);

    if (! $user) {
        abort(401, 'Autentikasi Desktop Companion gagal.');
    }

    // Periksa hak akses suara
    $canAccess = $user->isAdmin()
        || $sound->user_id === $user->id
        || $sound->isPublic();

    if (! $canAccess) {
        abort(403, 'Anda tidak memiliki akses ke suara ini.');
    }

    $disk = $sound->getDisk();

    if (! Storage::disk($disk)->exists($sound->file_path)) {
        abort(404, 'File audio tidak ditemukan.');
    }

    $sound->increment('play_count');

    return response()->stream(
        function () use ($disk, $sound): void {
            $stream = Storage::disk($disk)->readStream($sound->file_path);

            if (! is_resource($stream)) {
                abort(404, 'File audio tidak ditemukan.');
            }

            fpassthru($stream);
            fclose($stream);
        },
        200,
        [
            'Content-Type' => $sound->mime_type ?: 'audio/mpeg',
            'Content-Disposition' => 'inline; filename="' . addslashes($sound->file_name_original) . '"',
            'Cache-Control' => 'private, no-store',
        ]
    );
}
    /**
 * Endpoint API Sinkronisasi Hotkey untuk Desktop Companion.
 * Route: GET /api/desktop/hotkeys
 * Mendukung autentikasi Web Session atau HMAC melalui HTTP Header.
 */
    public function apiDesktopSync(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

// Jika bukan request dari sesi website, gunakan autentikasi Desktop Companion.
if (! $user) {
    $user = $this->authenticateDesktop($request);
}

        if (! $user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Autentikasi gagal. Silakan login kembali di aplikasi desktop.',
            ], 401);
        }

        $stopAllHotkey = $user->settings?->stop_all_hotkey ?? 'End';
        $masterVolume = $user->settings?->master_volume ?? 80;

        $hotkeys = $user->hotkeys()
            ->with('sound')
            ->get()
            ->filter(fn($hk) => $hk->sound !== null)
            ->map(function ($hk) {
                return [
                    'id'          => $hk->id,
                    'key_combo'   => $hk->key_combo,
                    'label'       => $hk->display_label,
                    'sound_id'    => $hk->sound_id,
                    'sound_title' => $hk->sound->title,
                    'play_url' => route('api.desktop.sounds.play', $hk->sound),
                ];
            })->values();

        return response()->json([
            'status'   => 'success',
            'user'     => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
            'settings' => [
                'stop_all_hotkey' => $stopAllHotkey,
                'master_volume'   => $masterVolume,
            ],
            'hotkeys'  => $hotkeys,
        ]);
    }

    /**
     * Hapus pemetaan hotkey.
     * Route: DELETE /hotkeys/{hotkey}
     */
    public function destroy(Request $request, Hotkey $hotkey): RedirectResponse
    {
        $user = $request->user();

        // Pastikan hanya pemilik yang bisa menghapus hotkey miliknya
        if ($hotkey->user_id !== $user->id) {
            abort(403, 'Anda tidak memiliki hak untuk menghapus hotkey ini.');
        }

        $combo = $hotkey->key_combo;
        $hotkey->delete();

        return back()->with('success', "Hotkey [{$combo}] berhasil dihapus.");
    }
}
