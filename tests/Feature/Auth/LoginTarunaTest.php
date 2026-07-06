<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\TestCase;

class LoginTarunaTest extends TestCase
{
    public function test_taruna_can_login_with_correct_credentials(): void
    {
        User::factory()->create([
            'npm' => '20241234',
            'password' => 'rahasia123',
        ]);

        $response = $this->postJson('/api/login/taruna', [
            'npm' => '20241234',
            'password' => 'rahasia123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['data' => ['user', 'token']]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'npm' => '20241234',
            'password' => 'rahasia123',
        ]);

        $response = $this->postJson('/api/login/taruna', [
            'npm' => '20241234',
            'password' => 'salah',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_fails_with_unknown_npm(): void
    {
        $response = $this->postJson('/api/login/taruna', [
            'npm' => '99999999',
            'password' => 'apapun',
        ]);

        $response->assertStatus(422);
    }
}
