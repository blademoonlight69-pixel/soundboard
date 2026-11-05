<?php

namespace App\Console\Commands;

use App\Models\ModerationLog;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Artisan Command: make:super-admin
 *
 * Cara aman membuat akun Super Admin baru tanpa melalui web.
 * Perintah ini bersifat interaktif — meminta input dari terminal secara langsung.
 *
 * Penggunaan:
 *   php artisan make:super-admin
 *
 * KEAMANAN:
 * - Password dimasukkan via prompt terminal (tidak ditampilkan di layar)
 * - Password langsung di-hash oleh bcrypt / hashing bawaan Laravel
 * - Tidak ada password hardcoded di kode sumber atau file seeder
 * - Command ini hanya bisa dijalankan oleh operator yang memiliki akses konsol server
 */
class MakeSuperAdminCommand extends Command
{
    /**
     * Nama dan signature perintah.
     * Dipanggil dengan: php artisan make:super-admin
     */
    protected $signature = 'make:super-admin';

    /**
     * Deskripsi perintah yang tampil di php artisan list.
     */
    protected $description = 'Buat akun Super Admin baru secara aman melalui terminal';

    /**
     * Jalankan perintah.
     */
    public function handle(): int
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════════╗');
        $this->info('║    MiniSoundboard — Buat Super Admin Baru    ║');
        $this->info('╚══════════════════════════════════════════════╝');
        $this->info('');
        $this->warn('Akun yang dibuat akan memiliki hak akses TERTINGGI (Super Admin),');
        $this->warn('termasuk wewenang mengelola dan mengangkat administrator.');
        $this->info('');

        // ── Input nama ──
        $name = $this->ask('Nama Lengkap / Username Super Admin');

        // ── Input email ──
        $email = $this->ask('Email Super Admin');

        // Cek apakah email sudah digunakan
        if (User::where('email', $email)->exists()) {
            $this->error('Email "' . $email . '" sudah digunakan oleh akun lain.');
            return Command::FAILURE;
        }

        // ── Input password (tersembunyi) ──
        $password = $this->secret('Password (minimal 8 karakter)');

        // ── Konfirmasi password (tersembunyi) ──
        $passwordConfirmation = $this->secret('Konfirmasi Password');

        // Validasi input
        $validator = Validator::make([
            'name'                  => $name,
            'email'                 => $email,
            'password'              => $password,
            'password_confirmation' => $passwordConfirmation,
        ], [
            'name'     => ['required', 'string', 'min:3', 'max:100'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required'      => 'Nama wajib diisi.',
            'name.min'           => 'Nama minimal 3 karakter.',
            'email.required'     => 'Email wajib diisi.',
            'email.email'        => 'Format email tidak valid.',
            'email.unique'       => 'Email sudah terdaftar.',
            'password.required'  => 'Password wajib diisi.',
            'password.min'       => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
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
        $this->info('Data Super Admin yang akan dibuat:');
        $this->table(['Field', 'Value'], [
            ['Nama',  $name],
            ['Email', $email],
            ['Role',  'super_admin (Tingkat Tertinggi)'],
        ]);

        if (! $this->confirm('Lanjutkan pembuatan akun Super Admin ini?', true)) {
            $this->info('Pembuatan Super Admin dibatalkan.');
            return Command::SUCCESS;
        }

        // Buat akun Super Admin
        $superAdmin = new User();
        $superAdmin->name      = $name;
        $superAdmin->email     = $email;
        $superAdmin->password  = Hash::make($password);
        $superAdmin->role      = 'super_admin';
        $superAdmin->is_active = true;
        $superAdmin->save();

        // Buat user setting default
        UserSetting::create([
            'user_id'       => $superAdmin->id,
            'master_volume' => 80,
            'theme'         => 'dark',
        ]);

        // Catat ke Audit Log
        ModerationLog::record(
            $superAdmin,
            'user',
            $superAdmin->id,
            'create_super_admin',
            'Akun Super Admin dibuat secara aman via terminal Artisan',
            [
                'email' => $superAdmin->email,
                'role'  => 'super_admin',
            ]
        );

        $this->info('');
        $this->info('✓ Akun Super Admin berhasil dibuat!');
        $this->info('  ID    : ' . $superAdmin->id);
        $this->info('  Nama  : ' . $superAdmin->name);
        $this->info('  Email : ' . $superAdmin->email);
        $this->info('  Role  : ' . $superAdmin->role);
        $this->info('');
        $this->warn('Simpan kredensial ini di tempat yang aman. Password tidak dapat dilihat kembali.');

        return Command::SUCCESS;
    }
}
