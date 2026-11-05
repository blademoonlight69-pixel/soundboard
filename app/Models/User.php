<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Model User — mewakili akun pengguna aplikasi.
 *
 * Role:
 * - 'admin' : Pengelola platform, bisa moderasi suara dan kelola user
 * - 'user'  : Pengguna biasa, bisa upload dan memutar suara
 *
 * PENTING: Admin tidak bisa mengubah isi/kepemilikan suara user lain.
 * Admin hanya berwenang menyembunyikan (hide) atau mengembalikan (unhide) suara.
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Kolom yang boleh diisi secara massal (mass assignment).
     * 'role' sengaja TIDAK dimasukkan ke sini agar tidak bisa diubah
     * melalui form registrasi publik.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        // TIDAK ADA 'role' di sini — role diatur melalui Artisan command/seeder saja
    ];

    /**
     * Kolom yang disembunyikan saat model dikonversi ke array/JSON.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casting otomatis tipe data kolom.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',     // Password otomatis di-hash saat disimpan
            'is_active'         => 'boolean',
        ];
    }

    // =========================================================================
    // HELPER METHODS — Memeriksa role dan status user
    // =========================================================================

    /**
     * Cek apakah user adalah Super Admin (role tertinggi).
     * Memiliki wewenang mengelola administrator dan konfigurasi kritis.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * Cek apakah user memiliki hak akses admin (Super Admin atau Admin).
     * Super Admin mewarisi semua hak akses Admin.
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'super_admin'], true);
    }

    /**
     * Cek apakah user adalah admin biasa (bukan super_admin).
     */
    public function isExactAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Cek apakah user adalah user biasa (bukan admin maupun super_admin).
     */
    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    /**
     * Cek apakah akun user aktif.
     * Akun yang dinonaktifkan tidak bisa login.
     */
    public function isActive(): bool
    {
        return (bool) ($this->is_active ?? true);
    }

    // =========================================================================
    // PROTEKSI ROLE & ANTI-LOCKOUT
    // =========================================================================

    /**
     * Hitung jumlah Super Admin yang aktif di database.
     */
    public static function activeSuperAdminCount(): int
    {
        return static::where('role', 'super_admin')->where('is_active', true)->count();
    }

    /**
     * Cek apakah user ini adalah satu-satunya Super Admin aktif yang tersisa.
     */
    public function isLastActiveSuperAdmin(): bool
    {
        return $this->isSuperAdmin() && $this->is_active && static::activeSuperAdminCount() <= 1;
    }

    /**
     * Validasi apakah user ini boleh dinonaktifkan oleh aktor.
     * - Admin biasa TIDAK BOLEH menonaktifkan Super Admin atau Admin lain.
     * - Super Admin terakhir TIDAK BOLEH dinonaktifkan (Anti-Lockout).
     */
    public function canBeDeactivatedBy(User $actor): bool
    {
        // Hanya Super Admin yang berhak menonaktifkan akun admin/super_admin
        if ($this->isAdmin() && ! $actor->isSuperAdmin()) {
            return false;
        }

        // Anti-lockout: Super Admin terakhir tidak boleh dinonaktifkan
        if ($this->isLastActiveSuperAdmin()) {
            return false;
        }

        return true;
    }

    /**
     * Validasi apakah role user ini boleh diubah ke newRole oleh aktor.
     * - Hanya Super Admin yang berhak mengubah role siapapun.
     * - Super Admin terakhir tidak boleh didemosi ke role lain (Anti-Lockout).
     */
    public function canChangeRoleTo(User $actor, string $newRole): bool
    {
        if (! $actor->isSuperAdmin()) {
            return false;
        }

        if (! in_array($newRole, ['super_admin', 'admin', 'user'], true)) {
            return false;
        }

        // Anti-lockout: Super Admin terakhir tidak boleh didemosi
        if ($this->isLastActiveSuperAdmin() && $newRole !== 'super_admin') {
            return false;
        }

        return true;
    }

    /**
     * Validasi apakah user ini boleh dihapus oleh aktor.
     * - Admin biasa TIDAK BOLEH menghapus sesama admin atau super_admin.
     * - Super Admin terakhir TIDAK BOLEH dihapus (Anti-Lockout).
     */
    public function canBeDeletedBy(User $actor): bool
    {
        if ($this->isAdmin() && ! $actor->isSuperAdmin()) {
            return false;
        }

        if ($this->isLastActiveSuperAdmin()) {
            return false;
        }

        return true;
    }

    // =========================================================================
    // RELATIONSHIPS — Relasi ke model lain
    // =========================================================================

    /**
     * Suara-suara yang diunggah oleh user ini.
     */
    public function sounds(): HasMany
    {
        return $this->hasMany(Sound::class);
    }

    /**
     * Suara-suara yang ditandai favorit oleh user ini.
     */
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    /**
     * Pengaturan personal user (volume, tema).
     * Relasi one-to-one.
     */
    public function settings(): HasOne
    {
        return $this->hasOne(UserSetting::class);
    }

    /**
     * Hotkey yang didefinisikan oleh user ini.
     */
    public function hotkeys(): HasMany
    {
        return $this->hasMany(Hotkey::class);
    }

    /**
     * Laporan yang dibuat oleh user ini.
     */
    public function reports(): HasMany
    {
        return $this->hasMany(SoundReport::class, 'reporter_id');
    }
}
