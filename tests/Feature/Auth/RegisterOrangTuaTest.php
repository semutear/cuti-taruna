<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\TestCase;

class RegisterOrangTuaTest extends TestCase
{
    public function test_orang_tua_can_register_linked_to_existing_taruna(): void
    {
        $anak = User::factory()->create(['npm' => '20241234']);

        $response = $this->postJson('/api/register/orangtua', [
            'nama_lengkap' => 'Siti Aminah',
            'username' => 'siti_ortu',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'npm_anak' => '20241234',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.anak.npm', '20241234');

        $this->assertDatabaseHas('users', [
            'username' => 'siti_ortu',
            'role' => 'orang_tua',
            'child_id' => $anak->id,
        ]);
    }

    public function test_register_orang_tua_fails_when_npm_anak_not_found(): void
    {
        $response = $this->postJson('/api/register/orangtua', [
            'nama_lengkap' => 'Siti Aminah',
            'username' => 'siti_ortu',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'npm_anak' => '00000000',
        ]);

        $response->assertStatus(404);
    }

    public function test_register_orang_tua_fails_when_child_already_has_account(): void
    {
        $anak = User::factory()->create(['npm' => '20241234']);
        User::factory()->orangTua($anak->id)->create(['username' => 'existing_ortu']);

        $response = $this->postJson('/api/register/orangtua', [
            'nama_lengkap' => 'Siti Aminah',
            'username' => 'siti_ortu_baru',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'npm_anak' => '20241234',
        ]);

        $response->assertStatus(422);
    }

    public function test_register_orang_tua_fails_when_username_taken(): void
    {
        $anak = User::factory()->create(['npm' => '20241234']);
        User::factory()->orangTua()->create(['username' => 'siti_ortu']);

        $response = $this->postJson('/api/register/orangtua', [
            'nama_lengkap' => 'Siti Aminah',
            'username' => 'siti_ortu',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'npm_anak' => '20241234',
        ]);

        $response->assertStatus(422);
    }
}
