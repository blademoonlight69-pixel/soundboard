<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Model UserSetting — preferensi personal user (relasi one-to-one dengan User). */
class UserSetting extends Model
{
    protected $fillable = [
        'user_id',
        'master_volume',
        'theme',
    ];

    protected function casts(): array
    {
        return [
            'master_volume' => 'integer',
        ];
    }

    /** Pemilik setting ini */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
