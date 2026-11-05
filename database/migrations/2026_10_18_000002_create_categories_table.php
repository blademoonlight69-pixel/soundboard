<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel 'categories' menyimpan kategori sound effect.
 * Kategori dikelola oleh Admin - user biasa tidak bisa membuat
 * atau mengubah kategori global.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();

            // Nama kategori yang ditampilkan ke user, contoh: "Gaming", "Meme"
            $table->string('name', 100)->unique();

            // Slug digunakan untuk URL dan filter, contoh: "gaming", "funny-sounds"
            // Dibuat otomatis dari name saat menyimpan
            $table->string('slug', 100)->unique();

            // Deskripsi opsional untuk halaman admin
            $table->text('description')->nullable();

            // Emoji atau nama icon untuk ditampilkan di UI, contoh: "🎮", "😂"
            $table->string('icon', 10)->nullable();

            // Menentukan urutan tampil kategori di UI (angka kecil = tampil lebih dulu)
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
