<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration ini MENAMBAH kolom ke tabel 'users' yang sudah dibuat
 * oleh migration bawaan Laravel. Kita TIDAK mengubah migration bawaan
 * agar tetap aman dan tidak konflik jika ada data yang sudah ada.
 */
return new class extends Migration
{
    /**
     * Jalankan migration: tambah kolom role dan is_active.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Kolom 'role' membedakan Admin dan User.
            // Nilai default 'user' memastikan semua akun baru adalah User biasa.
            // Admin HANYA bisa dibuat melalui Artisan command atau seeder - tidak dari form publik.
            $table->enum('role', ['super_admin', 'admin', 'user'])->default('user')->after('password');

            // Kolom 'is_active' memungkinkan admin menonaktifkan akun tanpa menghapus data.
            // Akun yang tidak aktif tidak bisa login meskipun password benar.
            $table->boolean('is_active')->default(true)->after('role');
        });
    }

    /**
     * Balikkan migration: hapus kolom yang baru ditambahkan.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'is_active']);
        });
    }
};
