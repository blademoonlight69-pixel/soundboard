<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * DatabaseSeeder — seeder utama yang memanggil seeder-seeder lain.
 *
 * Urutan pemanggilan penting karena ada ketergantungan data.
 * Cara menjalankan:
 *   php artisan db:seed
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed database aplikasi.
     */
    public function run(): void
    {
        // 1. Isi kategori default terlebih dahulu
        //    (diperlukan sebelum data sound bisa dibuat)
        $this->call(CategorySeeder::class);

        // Catatan: AdminSeeder tidak dipanggil dari sini
        // karena pembuatan admin dilakukan via: php artisan make:admin
        // Ini memastikan kredensial admin tidak pernah hardcode di kode seeder.
        //
        // Jika Anda perlu seeder admin untuk keperluan testing/development lokal,
        // buat file AdminSeeder.php terpisah dan panggil secara eksplisit:
        //   php artisan db:seed --class=AdminSeeder
    }
}
