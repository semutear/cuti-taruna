<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    public function test_taruna_can_register_successfully(): void
    {
        $response = $this->postJson('/api/register', [
            'nama_lengkap' => 'Budi Santoso',
            'npm' => '20241234',
            'password' => 'password123',
            'nama_ibu' => 'Siti Aminah',
            'tanggal_lahir' => '2005-01-01',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.user.npm', '20241234')
            ->assertJsonStructure(['data' => ['user', 'token']]);

        $this->assertDatabaseHas('users', [
            'npm' => '20241234',
            'role' => 'taruna',
        ]);
    }

    public function test_register_fails_when_npm_already_taken(): void
    {
        User::factory()->create(['npm' => '20241234']);

        $response = $this->postJson('/api/register', [
            'nama_lengkap' => 'Budi Santoso',
            'npm' => '20241234',
            'password' => 'password123',
            'nama_ibu' => 'Siti Aminah',
            'tanggal_lahir' => '2005-01-01',
        ]);

        $response->assertStatus(422);
    }

    public function test_register_fails_when_required_fields_missing(): void
    {
        $response = $this->postJson('/api/register', [
            'nama_lengkap' => 'Budi Santoso',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['npm', 'password', 'nama_ibu', 'tanggal_lahir']);
    }
}
