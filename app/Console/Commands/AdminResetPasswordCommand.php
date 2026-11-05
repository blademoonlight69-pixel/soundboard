<?php

namespace App\Console\Commands;

use App\Models\ModerationLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Artisan Command: admin:reset-password
 *
 * Mereset password akun Super Admin yang sudah ada tanpa menghapus atau
 * mengubah data lainnya (email, role, status akun tetap sama).
 *
 * Penggunaan:
 *   php artisan admin:reset-password
 *
 * KEAMANAN:
 * - Password dimasukkan via prompt terminal (tidak ditampilkan di layar)
 * - Password langsung di-hash oleh bcrypt / hashing bawaan Laravel
 * - Tidak ada password hardcoded di kode sumber atau file seeder
 * - Command ini hanya bisa dijalankan oleh operator yang memiliki akses konsol server
 * - Audit log dicatat ke ModerationLog jika tabel tersedia
 */
class AdminResetPasswordCommand extends Command
{
    /**
     * Nama dan signature perintah.
     * Dipanggil dengan: php artisan admin:reset-password
     */
    protected $signature = 'admin:reset-password';

    /**
     * Deskripsi perintah yang tampil di php artisan list.
     */
    protected $description = 'Reset password akun Super Admin yang sudah ada secara aman melalui terminal';

    /**
     * Jalankan perintah.
     */
    public function handle(): int
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════════╗');
        $this->info('║   MiniSoundboard — Reset Password Super Admin   ║');
        $this->info('╚══════════════════════════════════════════════╝');
        $this->info('');

        // ── Input email ──
        $email = $this->ask('Email Super Admin');

        // Cari akun berdasarkan email
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error('Akun dengan email "' . $email . '" tidak ditemukan.');
            return Command::FAILURE;
        }

        // Pastikan role adalah super_admin
        if (! $user->isSuperAdmin()) {
            $this->error('Akun dengan email "' . $email . '" bukan Super Admin (role: ' . $user->role . ').');
            return Command::FAILURE;
        }

        // ── Input password baru (tersembunyi) ──
        $password = $this->secret('Password baru (minimal 8 karakter)');

        // ── Konfirmasi password baru (tersembunyi) ──
        $passwordConfirmation = $this->secret('Konfirmasi password baru');

        // Validasi input
        $validator = Validator::make([
            'password'              => $password,
            'password_confirmation' => $passwordConfirmation,
        ], [
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ], [
            'password.required'              => 'Password wajib diisi.',
            'password.min'                   => 'Password minimal 8 karakter.',
            'password.confirmed'             => 'Konfirmasi password tidak cocok.',
            'password_confirmation.required' => 'Konfirmasi password wajib diisi.',
        ]);

        if ($validator->fails()) {
            $this->error('Validasi gagal:');
            foreach ($validator->errors()->all() as $error) {
                $this->error('  • ' . $error);
            }
            return Command::FAILURE;
        }

        // Konfirmasi sebelum commit ke database
        $this->info('');
        $this->info('Data akun yang akan diubah password-nya:');
        $this->table(['Field', 'Value'], [
            ['Nama',  $user->name],
            ['Email', $user->email],
            ['Role',  'super_admin'],
        ]);

        if (! $this->confirm('Lanjutkan reset password untuk akun ini?', true)) {
            $this->info('Reset password dibatalkan.');
            return Command::SUCCESS;
        }

        // Perbarui hanya kolom password. Jangan mengubah email, role, is_active, atau data lainnya.
        $user->password = Hash::make($password);
        $user->save();

        // Catat ke audit log (ModerationLog). Jika tabel tidak tersedia, jangan gagalkan reset password.
        try {
            ModerationLog::record(
                $user,
                'user',
                $user->id,
                'reset_super_admin_password',
                'Password Super Admin direset via terminal Artisan',
                [
                    'email' => $user->email,
                    'role'  => 'super_admin',
                ]
            );
        } catch (\Throwable $e) {
            // Audit log tidak boleh menghentikan proses reset password
            $this->warn('Peringatan: Tidak dapat mencatat audit log (' . $e->getMessage() . ').');
        }

        $this->info('');
        $this->info('✓ Password Super Admin berhasil diubah!');
        $this->info('  ID    : ' . $user->id);
        $this->info('  Nama  : ' . $user->name);
        $this->info('  Email : ' . $user->email);
        $this->info('  Role  : ' . $user->role);
        $this->info('');
        $this->warn('Simpan password baru di tempat yang aman. Password tidak dapat dilihat kembali.');

        return Command::SUCCESS;
    }
}