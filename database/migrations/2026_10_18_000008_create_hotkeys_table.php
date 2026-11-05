<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel 'hotkeys' menyimpan pemetaan tombol keyboard ke suara tertentu.
 * Setiap user bisa menetapkan hotkey berbeda untuk suara yang sama.
 * Satu user tidak boleh menggunakan key combo yang sama dua kali (UNIQUE constraint).
 *
 * CATATAN PENTING tentang keterbatasan hotkey browser:
 * Hotkey yang didefinisikan di sini hanya aktif ketika tab browser sedang dalam fokus.
 * Untuk hotkey yang berfungsi saat Roblox atau aplikasi lain aktif,
 * diperlukan integrasi AutoHotkey v2 yang terpisah (Tahap 5).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotkeys', function (Blueprint $table) {
            $table->id();

            // Pemilik hotkey. Jika user dihapus, hotkey-nya ikut dihapus.
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Suara yang akan diputar. Jika suara dihapus, hotkey-nya ikut dihapus.
            $table->foreignId('sound_id')->constrained('sounds')->onDelete('cascade');

            // Kombinasi tombol keyboard, contoh: "F1", "Ctrl+1", "Alt+Z"
            // Disimpan dalam format yang konsisten (ditentukan oleh JavaScript di frontend)
            $table->string('key_combo', 50);

            // Label kustom opsional untuk hotkey, contoh: "Suara Kemenangan"
            // Jika NULL, gunakan judul suara sebagai label
            $table->string('label', 100)->nullable();

            $table->timestamps();

            // Satu user tidak boleh menetapkan key combo yang sama untuk dua suara berbeda
            $table->unique(['user_id', 'key_combo']);

            // Index untuk load semua hotkey user saat halaman dibuka
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotkeys');
    }
};
