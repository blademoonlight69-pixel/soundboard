<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * CategorySeeder — mengisi kategori default aplikasi.
 *
 * Menggunakan firstOrCreate() sehingga aman dijalankan berulang kali
 * tanpa menghasilkan data duplikat.
 *
 * Cara menjalankan:
 *   php artisan db:seed --class=CategorySeeder
 */
class CategorySeeder extends Seeder
{
    /**
     * Daftar kategori default.
     * Kolom 'slug' dibuat otomatis oleh model Category (via booted()).
     */
    public function run(): void
    {
        $categories = [
            [
                'name'        => 'Gaming',
                'slug'        => 'gaming',
                'description' => 'Suara efek untuk game, sinyal victory, defeat, dan gameplay umum.',
                'icon'        => '🎮',
                'sort_order'  => 1,
            ],
            [
                'name'        => 'Meme',
                'slug'        => 'meme',
                'description' => 'Suara meme viral dan populer dari internet.',
                'icon'        => '😂',
                'sort_order'  => 2,
            ],
            [
                'name'        => 'Funny Sounds',
                'slug'        => 'funny-sounds',
                'description' => 'Suara lucu dan menghibur untuk berbagai situasi.',
                'icon'        => '🤣',
                'sort_order'  => 3,
            ],
            [
                'name'        => 'Roblox',
                'slug'        => 'roblox',
                'description' => 'Suara dari game Roblox dan komunitas Roblox.',
                'icon'        => '🧱',
                'sort_order'  => 4,
            ],
            [
                'name'        => 'Horror',
                'slug'        => 'horror',
                'description' => 'Suara menegangkan, jumpscare, dan atmosfer horror.',
                'icon'        => '👻',
                'sort_order'  => 5,
            ],
            [
                'name'        => 'Anime',
                'slug'        => 'anime',
                'description' => 'Suara efek dan quote dari anime populer.',
                'icon'        => '🌸',
                'sort_order'  => 6,
            ],
            [
                'name'        => 'Voice Effects',
                'slug'        => 'voice-effects',
                'description' => 'Efek suara suara manusia yang dimodifikasi.',
                'icon'        => '🎙️',
                'sort_order'  => 7,
            ],
            [
                'name'        => 'Custom Sounds',
                'slug'        => 'custom-sounds',
                'description' => 'Suara kustom dan kreasi original dari para pengguna.',
                'icon'        => '✨',
                'sort_order'  => 8,
            ],
            [
                'name'        => 'Other',
                'slug'        => 'other',
                'description' => 'Kategori lain yang tidak masuk ke kategori di atas.',
                'icon'        => '📦',
                'sort_order'  => 9,
            ],
        ];

        foreach ($categories as $categoryData) {
            // firstOrCreate: buat jika belum ada, lewati jika sudah ada
            // Aman dijalankan berulang kali
            Category::firstOrCreate(
                ['slug' => $categoryData['slug']],  // Cari berdasarkan slug
                $categoryData                        // Buat dengan data ini jika tidak ditemukan
            );
        }

        $this->command->info('✓ ' . count($categories) . ' kategori default berhasil ditambahkan.');
    }
}
