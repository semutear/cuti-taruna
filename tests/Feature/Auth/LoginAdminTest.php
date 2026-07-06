<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\TestCase;

class LoginAdminTest extends TestCase
{
    public function test_admin_can_login_with_username_and_password(): void
    {
        User::factory()->admin()->create([
            'username' => 'admin',
            'password' => 'adminpass123',
        ]);

        $response = $this->postJson('/api/login/admin', [
            'username' => 'admin',
            'password' => 'adminpass123',
        ]);

        $response->assertStatus(200)->assertJsonPath('status', 'success');
    }

    public function test_login_fails_with_wrong_username(): void
    {
        User::factory()->admin()->create([
            'username' => 'admin',
            'password' => 'adminpass123',
        ]);

        $response = $this->postJson('/api/login/admin', [
            'username' => 'bukan_admin',
            'password' => 'adminpass123',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->admin()->create([
            'username' => 'admin',
            'password' => 'adminpass123',
        ]);

        $response = $this->postJson('/api/login/admin', [
            'username' => 'admin',
            'password' => 'salah',
        ]);

        $response->assertStatus(401);
    }
}
