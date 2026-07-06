<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CutiApplication>
 */
class CutiApplicationFactory extends Factory
{
    /**
     * Define the model's default state (cuti pending milik seorang taruna).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $mulai = fake()->dateTimeBetween('+1 week', '+2 weeks');

        return [
            'taruna_id' => User::factory(),
            'alamat_cuti' => [
                'jalan' => fake()->streetAddress(),
                'rt_rw' => '001/002',
                'kelurahan' => fake()->citySuffix(),
                'kecamatan' => fake()->city(),
                'kota' => fake()->city(),
                'provinsi' => fake()->state(),
            ],
            'tujuan' => 'orang_tua',
            'transportasi' => 'pribadi',
            'tanggal_mulai' => $mulai->format('Y-m-d'),
            'tanggal_selesai' => (clone $mulai)->modify('+2 days')->format('Y-m-d'),
            'status' => 'pending',
        ];
    }

    public function disetujuiOrtu(int $approverId): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'disetujui_ortu',
            'approved_by_orangtua' => $approverId,
            'approved_at' => now(),
        ]);
    }

    public function disetujui(int $approverId, int $finalizerId): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'disetujui',
            'approved_by_orangtua' => $approverId,
            'approved_at' => now(),
            'finalized_by_pengasuh' => $finalizerId,
            'finalized_at' => now(),
        ]);
    }

    public function ditolak(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'ditolak',
        ]);
    }
}
