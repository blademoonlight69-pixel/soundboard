<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Model SoundReport — laporan user terhadap suara yang melanggar aturan. */
class SoundReport extends Model
{
    protected $fillable = [
        'sound_id',
        'reporter_id',
        'reason',
        'description',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_note',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    /** Suara yang dilaporkan */
    public function sound(): BelongsTo
    {
        return $this->belongsTo(Sound::class);
    }

    /** User yang melaporkan */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /** Admin yang meninjau laporan */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** Cek apakah laporan sudah ditinjau */
    public function isReviewed(): bool
    {
        return $this->status !== 'pending';
    }
}
