<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel 'sound_reports' menyimpan laporan dari user terhadap suara publik
 * yang dianggap melanggar aturan.
 *
 * Workflow:
 * 1. User melapor suara → status 'pending'
 * 2. Admin meninjau laporan → status 'reviewed' (suara mungkin di-hide) atau 'dismissed'
 * 3. Satu user hanya bisa melaporkan satu suara satu kali (UNIQUE constraint)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sound_reports', function (Blueprint $table) {
            $table->id();

            // Suara yang dilaporkan. Jika suara dihapus, laporan ikut dihapus.
            $table->foreignId('sound_id')->constrained('sounds')->onDelete('cascade');

            // User yang melaporkan. Jika user dihapus, laporan ikut dihapus.
            $table->foreignId('reporter_id')->constrained('users')->onDelete('cascade');

            // Kategori alasan laporan
            $table->enum('reason', ['inappropriate', 'copyright', 'spam', 'other']);

            // Penjelasan tambahan dari pelapor (opsional)
            $table->text('description')->nullable();

            // Status penanganan laporan oleh admin
            $table->enum('status', ['pending', 'reviewed', 'dismissed'])->default('pending');

            // Admin yang meninjau laporan ini
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');

            // Kapan laporan ditinjau
            $table->timestamp('reviewed_at')->nullable();

            // Catatan admin saat meninjau (contoh: "Suara sudah di-hide karena...")
            $table->text('review_note')->nullable();

            $table->timestamps();

            // Satu user hanya bisa melaporkan satu suara satu kali
            $table->unique(['sound_id', 'reporter_id']);

            // Index untuk query admin: "semua laporan yang belum ditinjau"
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sound_reports');
    }
};
