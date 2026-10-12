<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SuperadminSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('role', User::ROLE_SUPERADMIN)->exists()) {
            $this->command?->info('Akun Superadmin sudah ada, seeder dilewati.');

            return;
        }

        $cfg = config('absensi.superadmin');
        $password = (string) ($cfg['password'] ?? '');

        if (strlen($password) < 12) {
            $this->command?->error(
                'SUPERADMIN_PASSWORD di file .env belum diisi atau kurang dari 12 karakter. '
                .'Akun Superadmin TIDAK dibuat. Isi dulu lalu jalankan: php artisan db:seed'
            );

            return;
        }

        $user = new User([
            'username' => strtolower($cfg['username']),
            'nama' => $cfg['nama'],
            'email' => $cfg['email'] ?: null,
            'password' => $password,
        ]);
        $user->role = User::ROLE_SUPERADMIN;
        $user->must_change_password = true; // wajib ganti password saat login pertama
        $user->save();

        $this->command?->info('Superadmin dibuat. Hapus SUPERADMIN_PASSWORD dari file .env sekarang.');
    }
}
