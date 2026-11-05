<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Model Hotkey — pemetaan tombol keyboard ke suara tertentu per user. */
class Hotkey extends Model
{
    protected $fillable = [
        'user_id',
        'sound_id',
        'key_combo',
        'label',
    ];

    /** Pemilik hotkey */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Suara yang akan diputar */
    public function sound(): BelongsTo
    {
        return $this->belongsTo(Sound::class);
    }

    /**
     * Dapatkan label yang ditampilkan.
     * Jika label kustom tidak ada, gunakan judul suara.
     */
    public function getDisplayLabelAttribute(): string
    {
        return $this->label ?? ($this->sound->title ?? 'Unknown Sound');
    }
}
