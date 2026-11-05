<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * Model Sound — mewakili satu file audio yang diunggah user.
 *
 * STATUS SUARA:
 * - 'public'  : Terlihat di katalog publik
 * - 'private' : Hanya pemilik yang bisa melihat dan memutar
 * - 'hidden'  : Disembunyikan oleh admin (moderasi)
 *
 * KEAMANAN PENYIMPANAN:
 * - File 'private' → disk 'local'  (path: storage/app/private/sounds/{user_id}/)
 * - File 'public'  → disk 'public' (path: storage/app/public/sounds/{user_id}/)
 * - Saat status berubah, file dipindah antar disk oleh SoundController
 *
 * KEPEMILIKAN:
 * - Admin TIDAK bisa mengubah title, description, atau file suara milik user.
 * - Admin hanya bisa mengubah kolom status, hidden_reason, hidden_at, hidden_by.
 */
class Sound extends Model
{
    // Aktifkan soft delete: hapus dari tampilan tanpa hapus dari database
    use SoftDeletes;

    /**
     * Kolom yang boleh diisi secara massal.
     * Kolom moderasi (hidden_reason, hidden_at, hidden_by) sengaja tidak ada
     * di fillable — hanya boleh diubah melalui method khusus di controller admin.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'description',
        'file_path',
        'file_name_original',
        'file_size',
        'duration',
        'mime_type',
        'status',
    ];

    /**
     * Casting tipe data kolom.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_size'  => 'integer',
            'duration'   => 'float',
            'play_count' => 'integer',
            'hidden_at'  => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    // =========================================================================
    // HELPER METHODS — Memeriksa status suara
    // =========================================================================

    /** Cek apakah suara berstatus publik */
    public function isPublic(): bool
    {
        return $this->status === 'public';
    }

    /** Cek apakah suara berstatus pribadi */
    public function isPrivate(): bool
    {
        return $this->status === 'private';
    }

    /** Cek apakah suara disembunyikan oleh admin */
    public function isHidden(): bool
    {
        return $this->status === 'hidden';
    }

    /**
     * Dapatkan disk storage yang digunakan berdasarkan status suara.
     * - Public  → disk 'public' (bisa diakses via URL /storage/...)
     * - Private → disk 'local'  (hanya via controller yang cek izin)
     * - Hidden  → gunakan disk yang sebelumnya digunakan (tidak pindah saat di-hide)
     */
    public function getDisk(): string
    {
        return $this->status === 'public' ? 'public' : 'local';
    }

    /**
     * Dapatkan ukuran file dalam format yang mudah dibaca manusia.
     * Contoh: "2.5 MB", "512 KB"
     */
    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return $bytes . ' B';
    }

    /**
     * Dapatkan durasi dalam format mm:ss.
     * Contoh: "1:23", "0:45"
     */
    public function getDurationFormattedAttribute(): string
    {
        if (! $this->duration) {
            return '0:00';
        }

        $minutes = floor($this->duration / 60);
        $seconds = floor($this->duration % 60);

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    // =========================================================================
    // RELATIONSHIPS — Relasi ke model lain
    // =========================================================================

    /** Pemilik suara */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Admin yang menyembunyikan suara ini (nullable) */
    public function hiddenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hidden_by');
    }

    /** Kategori suara */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** User-user yang menandai suara ini sebagai favorit */
    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')
                    ->withTimestamps();
    }

    /** Laporan-laporan terhadap suara ini */
    public function reports(): HasMany
    {
        return $this->hasMany(SoundReport::class);
    }

    /** Hotkey yang menunjuk ke suara ini */
    public function hotkeys(): HasMany
    {
        return $this->hasMany(Hotkey::class);
    }
}
