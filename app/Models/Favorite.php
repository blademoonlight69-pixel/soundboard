<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Model Favorite — relasi antara user dan suara yang ditandai favorit. */
class Favorite extends Model
{
    protected $fillable = ['user_id', 'sound_id'];

    /** User yang menandai favorit */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Suara yang ditandai favorit */
    public function sound(): BelongsTo
    {
        return $this->belongsTo(Sound::class);
    }
}
