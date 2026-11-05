<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel 'moderation_logs' mencatat setiap tindakan moderasi yang dilakukan admin.
 * Tabel ini bersifat APPEND-ONLY (tidak boleh diedit atau dihapus sembarangan)
 * untuk menjaga transparansi dan akuntabilitas moderasi.
 *
 * Setiap kali admin menyembunyikan suara, mengembalikannya, atau menindak user,
 * satu baris baru ditambahkan ke tabel ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moderation_logs', function (Blueprint $table) {
            $table->id();

            // Admin yang melakukan tindakan. Set NULL jika akun admin dihapus,
            // tapi log tetap ada (tidak ikut dihapus) untuk keperluan audit.
            $table->foreignId('admin_id')->nullable()->constrained('users')->onDelete('set null');

            // Jenis entitas yang dikenai tindakan: 'sound', 'user', 'report'
            $table->string('target_type', 50);

            // ID dari entitas yang dikenai tindakan (ID suara, ID user, dll)
            $table->unsignedBigInteger('target_id');

            // Jenis tindakan yang dilakukan admin
            // Contoh: 'hide_sound', 'unhide_sound', 'ban_user', 'unban_user',
            //         'dismiss_report', 'delete_sound'
            $table->string('action', 50);

            // Alasan tindakan yang akan ditampilkan ke pemilik suara
            $table->text('reason')->nullable();

            // Data tambahan dalam format JSON untuk keperluan history/audit
            // Contoh: {"sound_title": "Funny Cat", "user_email": "user@example.com"}
            // Berguna karena entitas asli mungkin sudah dihapus di masa depan
            $table->json('meta')->nullable();

            $table->timestamps();

            // Index untuk query "semua log terkait suara X" atau "semua log terkait user Y"
            $table->index(['target_type', 'target_id']);

            // Index untuk query "semua tindakan yang dilakukan admin X"
            $table->index('admin_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moderation_logs');
    }
};
