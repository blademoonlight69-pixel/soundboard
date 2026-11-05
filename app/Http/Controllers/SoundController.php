<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Sound;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * SoundController — menangani katalog suara, koleksi suara pribadi,
 * upload audio, update status suara, dan streaming audio aman.
 */
class SoundController extends Controller
{
    /**
     * Tampilkan katalog suara publik (/sounds/catalog).
     */
    public function catalog(Request $request): View
    {
        $categories = Category::orderBy('sort_order')->get();

        $query = Sound::query()
            ->where('status', 'public')
            ->with(['user', 'category', 'favoritedBy']);

        // Filter Pencarian
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter Kategori
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        // Sorting
        $sort = $request->get('sort', 'latest');
        if ($sort === 'popular') {
            $query->orderByDesc('play_count');
        } elseif ($sort === 'title') {
            $query->orderBy('title');
        } else {
            $query->latest();
        }

        $sounds = $query->paginate(16)->withQueryString();

        return view('sounds.catalog', compact('sounds', 'categories', 'sort'));
    }

    /**
     * Tampilkan koleksi suara milik pengguna yang sedang login (/my-sounds).
     */
    public function mySounds(Request $request): View
    {
        $categories = Category::orderBy('sort_order')->get();
        $user = $request->user();

        $query = $user->sounds()->with(['category', 'favoritedBy']);

        // Filter status (all, public, private, hidden)
        if ($request->filled('status') && in_array($request->status, ['public', 'private', 'hidden'])) {
            $query->where('status', $request->status);
        }

        // Filter Pencarian
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $sounds = $query->latest()->paginate(16)->withQueryString();

        // Hitung statistik koleksi user
        $stats = [
            'total'   => $user->sounds()->count(),
            'public'  => $user->sounds()->where('status', 'public')->count(),
            'private' => $user->sounds()->where('status', 'private')->count(),
            'hidden'  => $user->sounds()->where('status', 'hidden')->count(),
        ];

        return view('sounds.my_sounds', compact('sounds', 'categories', 'stats'));
    }

    /**
     * Proses upload file suara baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'       => ['required', 'string', 'min:2', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'status'      => ['required', 'in:public,private'],
            'audio'       => [
                'required',
                'file',
                'max:10240', // Maks 10MB
                'mimes:mp3,wav,ogg,flac,aac,m4a',
            ],
        ], [
            'title.required'    => 'Judul suara wajib diisi.',
            'title.min'         => 'Judul minimal 2 karakter.',
            'title.max'         => 'Judul maksimal 100 karakter.',
            'status.required'   => 'Pilih status visibilitas suara.',
            'status.in'         => 'Status visibilitas harus publik atau privat.',
            'audio.required'    => 'File audio wajib diunggah.',
            'audio.max'         => 'Ukuran file audio maksimal 10 MB.',
            'audio.mimes'       => 'Format file audio harus MP3, WAV, OGG, FLAC, AAC, atau M4A.',
        ]);

        $file = $request->file('audio');
        $user = $request->user();

        // Tentukan disk penyimpanan:
        // 'public'  -> disk 'public' (storage/app/public/sounds/{user_id}/)
        // 'private' -> disk 'local'  (storage/app/private/sounds/{user_id}/)
        $disk = $validated['status'] === 'public' ? 'public' : 'local';
        $subDir = "sounds/{$user->id}";

        $extension = $file->getClientOriginalExtension();
        $safeName = Str::uuid() . '.' . $extension;
        $storedPath = $file->storeAs($subDir, $safeName, $disk);

        // Buat record Sound
        $sound = Sound::create([
            'user_id'            => $user->id,
            'category_id'        => $validated['category_id'] ?? null,
            'title'              => $validated['title'],
            'description'        => $validated['description'] ?? null,
            'file_path'          => $storedPath,
            'file_name_original' => $file->getClientOriginalName(),
            'file_size'          => $file->getSize() ?: 0,
            'mime_type'          => $file->getMimeType() ?: 'audio/mpeg',
            'status'             => $validated['status'],
        ]);

        return back()->with('success', "Suara '{$sound->title}' berhasil diunggah!");
    }

    /**
     * Perbarui metadata suara (judul, deskripsi, kategori, status).
     */
    public function update(Request $request, Sound $sound): RedirectResponse
    {
        $user = $request->user();

        // Hanya pemilik yang boleh mengubah konten suara
        if ($sound->user_id !== $user->id) {
            abort(403, 'Anda tidak memiliki izin untuk mengubah suara ini.');
        }

        $validated = $request->validate([
            'title'       => ['required', 'string', 'min:2', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'status'      => ['required', 'in:public,private'],
        ]);

        $oldStatus = $sound->status;
        $newStatus = $validated['status'];

        // Jika suara sebelumnya di-hide oleh admin, pemilik tidak bisa mengubahnya jadi public langsung
        if ($oldStatus === 'hidden' && ! $user->isAdmin()) {
            return back()->with('error', 'Suara yang sedang dimoderasi (hidden) tidak dapat diubah statusnya.');
        }

        // Pindahkan file jika status berubah antara private <-> public
        if ($oldStatus !== $newStatus && in_array($oldStatus, ['public', 'private'])) {
            $oldDisk = $oldStatus === 'public' ? 'public' : 'local';
            $newDisk = $newStatus === 'public' ? 'public' : 'local';

            if (Storage::disk($oldDisk)->exists($sound->file_path)) {
                $fileContent = Storage::disk($oldDisk)->get($sound->file_path);
                Storage::disk($newDisk)->put($sound->file_path, $fileContent);
                Storage::disk($oldDisk)->delete($sound->file_path);
            }
        }

        $sound->update([
            'title'       => $validated['title'],
            'description' => $validated['description'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'status'      => $newStatus,
        ]);

        return back()->with('success', "Suara '{$sound->title}' berhasil diperbarui.");
    }

    /**
     * Hapus suara.
     */
    public function destroy(Request $request, Sound $sound): RedirectResponse
    {
        $user = $request->user();

        // Pemilik atau Admin boleh menghapus
        if ($sound->user_id !== $user->id && ! $user->isAdmin()) {
            abort(403, 'Anda tidak memiliki hak untuk menghapus suara ini.');
        }

        $disk = $sound->getDisk();
        if (Storage::disk($disk)->exists($sound->file_path)) {
            Storage::disk($disk)->delete($sound->file_path);
        }

        $sound->delete();

        return back()->with('success', "Suara '{$sound->title}' berhasil dihapus.");
    }

    /**
     * Endpoint pemutaran audio dengan validasi izin di sisi server.
     * Route: GET /sounds/{sound}/play
     */
    public function play(Request $request, Sound $sound): Response|StreamedResponse
    {
        $user = $request->user();

        // 1. Cek Hak Akses (Otorisasi Server):
        // - Admin/Super Admin boleh dengar suara apapun untuk verifikasi/moderasi
        // - Pemilik boleh dengar suaranya sendiri
        // - Suara public boleh didengar user lain yang login
        // - Suara private / hidden DITOLAK untuk user lain
        $canAccess = false;

        if ($user && $user->isAdmin()) {
            $canAccess = true;
        } elseif ($user && $sound->user_id === $user->id) {
            $canAccess = true;
        } elseif ($sound->isPublic()) {
            $canAccess = true;
        }

        if (! $canAccess) {
            abort(403, 'Akses ditolak. Suara ini bersifat privat atau disembunyikan oleh moderator.');
        }

        $disk = $sound->getDisk();

        // 2. Cek keberadaan file di storage
        if (! Storage::disk($disk)->exists($sound->file_path)) {
            // Jika file fisik belum ada (misal dummy data seeder), kirim 404
            abort(404, 'File audio tidak ditemukan di server.');
        }

        // 3. Increment play counter
        $sound->increment('play_count');

        // 4. Stream response
        return response()->stream(
            function () use ($disk, $sound): void {
                $stream = Storage::disk($disk)->readStream($sound->file_path);

                if (! is_resource($stream)) {
                    abort(404, 'File audio tidak ditemukan di server.');
                }

                fpassthru($stream);
                fclose($stream);
            },
            200,
            [
                'Content-Type'        => $sound->mime_type ?: 'audio/mpeg',
                'Content-Disposition' => 'inline; filename="' . addslashes($sound->file_name_original) . '"',
                'Cache-Control'       => 'private, max-age=3600',
            ]
        );
    }
}
