<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\TestCase;

class LoginAdminTest extends TestCase
{
    public function test_admin_can_login_with_password_only(): void
    {
        User::factory()->admin()->create(['password' => 'admin123']);

        $response = $this->postJson('/api/login/admin', [
            'password' => 'admin123',
        ]);

        $response->assertStatus(200)->assertJsonPath('status', 'success');
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->admin()->create(['password' => 'admin123']);

        $response = $this->postJson('/api/login/admin', [
            'password' => 'salah',
        ]);

        $response->assertStatus(401);
    }
}
