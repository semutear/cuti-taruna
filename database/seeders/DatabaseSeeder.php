<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Hanya membuat akun ADMIN, karena admin tidak memiliki alur registrasi
        // publik. Taruna & Orang Tua sengaja TIDAK di-seed: taruna mendaftar
        // sendiri via /register, orang tua via /register/orangtua.
        //
        // SR-08/SR-11: password admin TIDAK di-hardcode. Set ADMIN_SEED_PASSWORD
        // di .env sebelum seeding; jika tidak diset, password digenerate acak dan
        // ditampilkan sekali di console (harus dicatat, tidak akan ditampilkan lagi).
        $password = env('ADMIN_SEED_PASSWORD');

        if (!$password) {
            $password = Str::random(16);
            $this->command?->warn("ADMIN_SEED_PASSWORD tidak diset di .env. Password admin digenerate: {$password} (SIMPAN, tidak akan ditampilkan lagi)");
        }

        User::create([
            'nama_lengkap' => 'Administrator',
            'username' => env('ADMIN_SEED_USERNAME', 'admin'),
            'password' => Hash::make($password),
            'role' => 'admin',
        ]);
    }
}
