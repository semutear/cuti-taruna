<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state (taruna).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama_lengkap' => fake()->name(),
            'npm' => fake()->unique()->numerify('##########'),
            'password' => static::$password ??= 'password',
            'role' => 'taruna',
            'nama_ibu' => fake()->name('female'),
            'tanggal_lahir' => fake()->date(),
        ];
    }

    /**
     * State: akun orang tua. Opsional terhubung ke taruna (child_id).
     */
    public function orangTua(?int $childId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'npm' => null,
            'nama_ibu' => null,
            'tanggal_lahir' => null,
            'role' => 'orang_tua',
            'tanggal_lahir_anak' => fake()->date(),
            'child_id' => $childId,
        ]);
    }

    /**
     * State: akun admin/pengasuh.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'npm' => null,
            'nama_ibu' => null,
            'tanggal_lahir' => null,
            'role' => 'admin',
        ]);
    }
}
