<?php

namespace App\Policies;

use App\Models\Sound;
use App\Models\User;

/**
 * SoundPolicy — mengatur siapa yang boleh melakukan apa terhadap Sound.
 *
 * PRINSIP UTAMA:
 * - Admin TIDAK bisa mengedit isi (title, description, file) suara milik user lain
 * - Admin hanya berwenang menyembunyikan (hide) dan mengembalikan (unhide) suara
 * - Pemilik bisa mengedit dan menghapus suara miliknya sendiri
 * - Akses audio selalu diperiksa di server, tidak hanya di tampilan
 */
class SoundPolicy
{
    /**
     * Cek apakah user bisa melihat/memutar suara ini.
     *
     * Izin diberikan jika:
     * - Suara berstatus 'public' (siapapun yang sudah login boleh mendengar)
     * - User adalah pemilik suara (bisa lihat suara pribadi miliknya sendiri)
     * - User adalah admin (bisa lihat semua suara untuk keperluan moderasi)
     */
    public function view(User $user, Sound $sound): bool
    {
        // Admin bisa melihat semua suara
        if ($user->isAdmin()) {
            return true;
        }

        // Pemilik bisa melihat suaranya sendiri, apapun statusnya
        if ($sound->user_id === $user->id) {
            return true;
        }

        // User lain hanya bisa melihat suara yang berstatus 'public'
        // Suara 'hidden' tidak terlihat oleh user lain (meski statusnya bukan private)
        return $sound->status === 'public';
    }

    /**
     * Cek apakah user bisa mengedit suara ini.
     *
     * PENTING: Hanya pemilik yang bisa mengedit.
     * Admin TIDAK bisa mengedit konten suara milik user lain.
     * Ini melindungi kepemilikan kreatif user.
     */
    public function update(User $user, Sound $sound): bool
    {
        return $sound->user_id === $user->id;
    }

    /**
     * Cek apakah user bisa menghapus suara ini.
     *
     * Pemilik bisa menghapus suaranya sendiri.
     * Admin bisa menghapus suara manapun untuk keperluan moderasi.
     */
    public function delete(User $user, Sound $sound): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $sound->user_id === $user->id;
    }

    /**
     * Cek apakah user bisa menyembunyikan suara (aksi moderasi).
     * Hanya admin yang boleh.
     */
    public function hide(User $user, Sound $sound): bool
    {
        return $user->isAdmin();
    }

    /**
     * Cek apakah user bisa mengembalikan suara yang tersembunyi.
     * Hanya admin yang boleh.
     */
    public function unhide(User $user, Sound $sound): bool
    {
        return $user->isAdmin();
    }

    /**
     * Cek apakah user bisa melaporkan suara ini.
     * User biasa bisa melaporkan suara publik.
     * Pemilik tidak bisa melaporkan suaranya sendiri.
     * Admin tidak perlu melaporkan (langsung hide saja).
     */
    public function report(User $user, Sound $sound): bool
    {
        // Admin tidak perlu melaporkan
        if ($user->isAdmin()) {
            return false;
        }

        // Pemilik tidak bisa melaporkan suaranya sendiri
        if ($sound->user_id === $user->id) {
            return false;
        }

        // Hanya suara publik yang bisa dilaporkan
        return $sound->status === 'public';
    }
}
