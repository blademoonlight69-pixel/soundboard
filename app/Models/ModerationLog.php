<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model ModerationLog — rekaman setiap tindakan moderasi admin.
 * Tabel ini bersifat append-only untuk keperluan audit.
 */
class ModerationLog extends Model
{
    protected $fillable = [
        'admin_id',
        'target_type',
        'target_id',
        'action',
        'reason',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta'       => 'array',  // JSON otomatis di-encode/decode
            'target_id'  => 'integer',
        ];
    }

    /** Admin yang melakukan tindakan */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Buat log moderasi baru dengan cara yang lebih mudah.
     * Contoh penggunaan:
     * ModerationLog::record($admin, 'sound', $sound->id, 'hide_sound', 'Melanggar aturan', ['title' => $sound->title]);
     */
    public static function record(
        User $admin,
        string $targetType,
        int $targetId,
        string $action,
        ?string $reason = null,
        array $meta = []
    ): self {
        return self::create([
            'admin_id'    => $admin->id,
            'target_type' => $targetType,
            'target_id'   => $targetId,
            'action'      => $action,
            'reason'      => $reason,
            'meta'        => $meta,
        ]);
    }
}
