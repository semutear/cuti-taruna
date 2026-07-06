<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Kolom yang bisa diisi (mass assignable)
     */
    protected $fillable = [
            'nama_lengkap',
    'npm',
    'username',
    'password',
    'role',
    'nama_ibu',
    'tanggal_lahir_anak',
    'tanggal_lahir',
    'child_id',
    ];

    /**
     * Kolom yang disembunyikan saat serialisasi (misal JSON)
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casting tipe data
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'tanggal_lahir_anak' => 'date', // otomatis jadi Carbon instance
        ];
    }

    // Relasi: satu user (taruna) memiliki banyak pengajuan cuti
    public function cutiApplications()
    {
        return $this->hasMany(CutiApplication::class, 'taruna_id');
    }

    // Relasi: satu user (orang tua) bisa menyetujui banyak cuti
    public function approvedCuti()
    {
        return $this->hasMany(CutiApplication::class, 'approved_by_orangtua');
    }
    public function child()
{
    return $this->belongsTo(User::class, 'child_id');
}
public function parent()
{
    return $this->hasOne(User::class, 'child_id');
}
}
