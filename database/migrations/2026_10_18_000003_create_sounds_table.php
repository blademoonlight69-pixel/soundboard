<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel 'sounds' adalah tabel utama aplikasi.
 * Setiap baris mewakili satu file audio yang diunggah oleh user.
 *
 * STATUS SUARA:
 * - 'public'  : Terlihat di katalog publik, bisa diputar semua user
 * - 'private' : Hanya pemilik yang bisa melihat dan memutar
 * - 'hidden'  : Disembunyikan oleh admin (moderasi). Pemilik masih bisa
 *               melihat info suaranya + alasan moderasi, tapi tidak muncul
 *               di katalog publik. File tidak langsung dihapus.
 *
 * KEAMANAN FILE:
 * - Audio 'private' disimpan di disk 'local' (storage/app/private/) → tidak bisa diakses via URL
 * - Audio 'public' disimpan di disk 'public' (storage/app/public/) → bisa diakses via /storage/
 * - Saat status berubah private↔public, file dipindahkan antar disk
 * - Akses audio SELALU melalui controller yang memeriksa izin, tidak hanya via URL langsung
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sounds', function (Blueprint $table) {
            $table->id();

            // Pemilik suara. Jika user dihapus, suaranya ikut dihapus (cascade).
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Kategori bersifat opsional. Jika kategori dihapus, set ke NULL (bukan hapus sound).
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null');

            // Nama suara yang ditampilkan ke user
            $table->string('title', 100);

            // Deskripsi opsional dari pemilik
            $table->text('description')->nullable();

            // Path file di storage, relatif terhadap disk yang digunakan.
            // Contoh: "sounds/1/uuid-filename.mp3"
            // Disk yang digunakan (local/public) ditentukan berdasarkan kolom 'status'
            $table->string('file_path', 500);

            // Nama file asli dari user (hanya untuk tampilan, BUKAN nama file di storage)
            $table->string('file_name_original', 255);

            // Ukuran file dalam bytes (untuk ditampilkan ke user)
            $table->unsignedBigInteger('file_size')->default(0);

            // Durasi audio dalam detik (diisi saat upload jika tersedia)
            $table->decimal('duration', 8, 2)->nullable();

            // MIME type dari file audio, contoh: "audio/mpeg", "audio/wav"
            $table->string('mime_type', 50)->nullable();

            // Status suara: public, private, atau hidden (oleh admin)
            $table->enum('status', ['public', 'private', 'hidden'])->default('private');

            // Kolom-kolom moderasi (diisi oleh admin saat menyembunyikan suara)
            // Admin TIDAK bisa mengubah isi/judul/deskripsi sound milik user
            $table->text('hidden_reason')->nullable();       // Alasan moderasi untuk ditampilkan ke pemilik
            $table->timestamp('hidden_at')->nullable();      // Kapan suara disembunyikan
            $table->foreignId('hidden_by')->nullable()       // Admin yang menyembunyikan
                  ->constrained('users')->onDelete('set null');

            // Jumlah berapa kali suara ini diputar
            $table->unsignedBigInteger('play_count')->default(0);

            // Soft delete: suara yang "dihapus" tidak langsung hilang dari database
            // Data tetap ada tapi tidak muncul di query normal
            $table->softDeletes();

            $table->timestamps();

            // Index untuk mempercepat query umum
            $table->index(['user_id', 'status']);           // "Semua suara milik user X yang publik"
            $table->index(['status', 'category_id']);       // "Filter katalog publik per kategori"
            $table->index('title');                          // Pencarian berdasarkan nama
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sounds');
    }
};
