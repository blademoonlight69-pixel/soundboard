<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Model Category — kategori sound effect.
 * Hanya admin yang bisa membuat, mengubah, atau menghapus kategori.
 */
class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * Suara-suara yang termasuk dalam kategori ini.
     */
    public function sounds(): HasMany
    {
        return $this->hasMany(Sound::class);
    }

    /**
     * Otomatis buat slug dari name saat model disimpan.
     * Contoh: "Funny Sounds" → "funny-sounds"
     */
    protected static function booted(): void
    {
        static::creating(function (Category $category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });

        static::updating(function (Category $category) {
            if ($category->isDirty('name') && empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }
}
