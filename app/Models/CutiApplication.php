<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CutiApplication extends Model
{
    use HasFactory;

    /**
     * Nama tabel (opsional, karena Laravel akan otomatis mendeteksi dari nama model jamak)
     */
    protected $table = 'cuti_applications';

    /**
     * Kolom yang dapat diisi
     */
    protected $fillable = [
    'taruna_id',
    'alamat_cuti', // json
    'tujuan',
    'nama_kerabat',
    'nomor_kerabat',
    'transportasi',
    'tiket_path',
    'tanggal_mulai',
    'tanggal_selesai',
    'status',
    'approved_by_orangtua',
    'approved_at',
    'finalized_by_pengasuh',
    'finalized_at',
];

protected $casts = [
    'alamat_cuti' => 'array', // otomatis decode/encode JSON
    'tanggal_mulai' => 'date',
    'tanggal_selesai' => 'date',
    'approved_at' => 'datetime',
    'finalized_at' => 'datetime',
];

    // Relasi ke taruna (pengguna dengan role taruna)
    public function taruna()
    {
        return $this->belongsTo(User::class, 'taruna_id');
    }

    // Relasi ke orang tua yang menyetujui (tahap 1)
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by_orangtua');
    }

    // Relasi ke pengasuh/admin yang memfinalisasi (tahap 2)
    public function finalizedBy()
    {
        return $this->belongsTo(User::class, 'finalized_by_pengasuh');
    }
}
