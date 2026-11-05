<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Artisan Command: make:admin
 *
 * Cara aman membuat akun Admin tanpa melalui form registrasi publik.
 * Perintah ini bersifat interaktif — minta input dari terminal secara langsung.
 *
 * Penggunaan:
 *   php artisan make:admin
 *
 * KEAMANAN:
 * - Password dimasukkan via prompt terminal (tidak ditampilkan di layar)
 * - Password langsung di-hash oleh Laravel
 * - Tidak ada password yang tersimpan di kode atau log
 * - Command ini hanya bisa dijalankan dari terminal server (bukan via web)
 */
class MakeAdminCommand extends Command
{
    /**
     * Nama dan signature perintah.
     * Dipanggil dengan: php artisan make:admin
     */
    protected $signature = 'make:admin';

    /**
     * Deskripsi perintah yang tampil di php artisan list
     */
    protected $description = 'Buat akun Admin baru secara aman melalui terminal';

    /**
     * Jalankan perintah.
     */
    public function handle(): int
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════╗');
        $this->info('║    MiniSoundboard — Buat Admin Baru  ║');
        $this->info('╚══════════════════════════════════════╝');
        $this->info('');
        $this->warn('Akun yang dibuat akan memiliki akses penuh ke panel admin.');
        $this->info('');

        // ── Minta input nama ──
        $name = $this->ask('Nama Admin');

        // ── Minta input email ──
        $email = $this->ask('Email Admin');

        // Cek apakah email sudah digunakan
        if (User::where('email', $email)->exists()) {
            $this->error('Email "' . $email . '" sudah digunakan oleh akun lain.');
            return Command::FAILURE;
        }

        // ── Minta input password (tidak ditampilkan di layar) ──
        $password = $this->secret('Password (minimal 8 karakter)');

        // ── Minta konfirmasi password ──
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
        ]);

        if ($validator->fails()) {
            $this->error('Input tidak valid:');
            foreach ($validator->errors()->all() as $error) {
                $this->error('  • ' . $error);
            }
            return Command::FAILURE;
        }

        // Konfirmasi sebelum membuat akun
        $this->info('');
        $this->info('Data Admin yang akan dibuat:');
        $this->table(['Field', 'Value'], [
            ['Nama',  $name],
            ['Email', $email],
            ['Role',  'admin'],
        ]);

        if (! $this->confirm('Lanjutkan membuat akun admin ini?')) {
            $this->info('Dibatalkan.');
            return Command::SUCCESS;
        }

        // Buat akun admin
        // Gunakan DB langsung untuk mengatur role 'admin' (tidak melalui mass assignment)
        $admin = new User();
        $admin->name      = $name;
        $admin->email     = $email;
        $admin->password  = Hash::make($password); // Hash manual agar eksplisit
        $admin->role      = 'admin';               // Set role admin secara langsung
        $admin->is_active = true;
        $admin->save();

        // Buat settings default untuk admin
        UserSetting::create([
            'user_id'       => $admin->id,
            'master_volume' => 80,
            'theme'         => 'dark',
        ]);

        $this->info('');
        $this->info('✓ Akun admin berhasil dibuat!');
        $this->info('  ID    : ' . $admin->id);
        $this->info('  Nama  : ' . $admin->name);
        $this->info('  Email : ' . $admin->email);
        $this->info('');
        $this->warn('Simpan kredensial ini dengan aman. Password tidak bisa dilihat lagi.');

        return Command::SUCCESS;
    }
}
