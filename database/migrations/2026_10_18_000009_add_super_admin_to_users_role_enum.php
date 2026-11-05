<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migration untuk menambahkan role 'super_admin' ke ENUM kolom 'role' pada tabel users.
 * Aman dan non-destruktif: tidak menghapus atau mengubah data pengguna yang sudah ada.
 */
return new class extends Migration
{
    /**
     * Jalankan migration: perluas enum role dengan 'super_admin'.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'admin', 'user') NOT NULL DEFAULT 'user'");
        }
    }

    /**
     * Balikkan migration: kembalikan enum role ke 'admin' dan 'user'.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Sebelum downgrade, pastikan user yang super_admin diset ke admin agar tidak truncate
            DB::statement("UPDATE users SET role = 'admin' WHERE role = 'super_admin'");
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'user') NOT NULL DEFAULT 'user'");
        }
    }
};
