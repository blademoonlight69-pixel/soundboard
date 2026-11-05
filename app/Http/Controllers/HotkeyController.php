<?php

namespace App\Http\Controllers;

use App\Models\Hotkey;
use App\Models\Sound;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return view('hotkeys.index', compact('hotkeys', 'availableSounds'));
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
                'label'    => $validated['label'] ?: $sound->title,
            ]);
            return back()->with('success', "Hotkey [{$keyCombo}] berhasil dialihkan ke '{$sound->title}'.");
        }

        // Buat hotkey baru
        Hotkey::create([
            'user_id'   => $user->id,
            'sound_id'  => $sound->id,
            'key_combo' => $keyCombo,
            'label'     => $validated['label'] ?: $sound->title,
        ]);

        return back()->with('success', "Hotkey [{$keyCombo}] untuk '{$sound->title}' berhasil diaktifkan! ⌨️");
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
