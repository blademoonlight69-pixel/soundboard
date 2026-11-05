<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel 'favorites' mencatat suara yang ditandai favorit oleh setiap user.
 * Relasi many-to-many antara users dan sounds.
 * Satu user hanya bisa menandai satu suara sebagai favorit satu kali (UNIQUE constraint).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();

            // Jika user dihapus, semua favoritnya ikut dihapus
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Jika suara dihapus, semua favorit yang menunjuk ke suara itu ikut dihapus
            $table->foreignId('sound_id')->constrained('sounds')->onDelete('cascade');

            $table->timestamps();

            // Satu user hanya bisa menandai satu suara sebagai favorit satu kali
            $table->unique(['user_id', 'sound_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
