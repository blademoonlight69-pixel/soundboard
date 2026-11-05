<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel 'user_settings' menyimpan preferensi personal setiap user.
 * Satu baris per user (relasi one-to-one).
 * Baris dibuat otomatis saat user pertama kali login (via observer atau controller).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();

            // Relasi one-to-one dengan users. UNIQUE memastikan satu user = satu baris setting.
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');

            // Volume master audio: 0-100 (disimpan sebagai integer persen)
            $table->unsignedTinyInteger('master_volume')->default(80);

            // Tema tampilan (untuk pengembangan di masa depan, default: 'dark')
            $table->string('theme', 20)->default('dark');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_settings');
    }
};
