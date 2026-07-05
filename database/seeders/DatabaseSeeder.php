<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Hanya membuat akun ADMIN, karena admin tidak memiliki alur registrasi
        // (tidak ada endpoint /register untuk admin). Taruna & Orang Tua sengaja
        // TIDAK di-seed: taruna mendaftar sendiri via /register, dan akun orang tua
        // dibuat otomatis saat login pertama berdasarkan data anaknya.
        User::create([
            'nama_lengkap' => 'Administrator',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
        ]);
    }
}
