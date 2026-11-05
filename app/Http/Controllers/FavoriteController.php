<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Sound;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * FavoriteController — mengelola suara favorit pengguna.
 */
class FavoriteController extends Controller
{
    /**
     * Tampilkan daftar suara favorit milik pengguna yang sedang login (/favorites).
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // Ambil suara-suara yang ditandai favorit oleh user ini
        // Privacy Guard: Hanya tampilkan suara yang berstatus public ATAU milik user sendiri
        $sounds = Sound::query()
            ->whereHas('favoritedBy', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->where(function ($q) use ($user) {
                $q->where('status', 'public')
                  ->orWhere('user_id', $user->id);
            })
            ->with(['user', 'category', 'favoritedBy'])
            ->latest()
            ->paginate(16)
            ->withQueryString();

        return view('sounds.favorites', compact('sounds'));
    }

    /**
     * Tandai atau batalkan tanda favorit pada suatu suara (Toggle Favorite).
     * Route: POST /sounds/{sound}/favorite
     */
    public function toggle(Request $request, Sound $sound): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        // Suara private orang lain atau suara hidden tidak boleh difavoritkan
        $canAccess = $sound->isPublic() || $sound->user_id === $user->id || $user->isAdmin();
        if (! $canAccess) {
            abort(403, 'Anda tidak dapat memfavoritkan suara privat atau suara yang disembunyikan.');
        }

        $existing = Favorite::where('user_id', $user->id)
            ->where('sound_id', $sound->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $favorited = false;
            $message = "Suara '{$sound->title}' dihapus dari daftar favorit.";
        } else {
            Favorite::create([
                'user_id'  => $user->id,
                'sound_id' => $sound->id,
            ]);
            $favorited = true;
            $message = "Suara '{$sound->title}' ditambahkan ke daftar favorit! ⭐";
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success'   => true,
                'favorited' => $favorited,
                'message'   => $message,
            ]);
        }

        return back()->with('success', $message);
    }
}
