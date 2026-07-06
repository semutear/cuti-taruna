<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\TestCase;

class LoginOrangTuaTest extends TestCase
{
    public function test_orang_tua_can_login_with_username_and_password(): void
    {
        $anak = User::factory()->create();
        User::factory()->orangTua($anak->id)->create([
            'username' => 'ibu_budi',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/login/orangtua', [
            'username' => 'ibu_budi',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.anak.id', $anak->id);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $anak = User::factory()->create();
        User::factory()->orangTua($anak->id)->create([
            'username' => 'ibu_budi',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/login/orangtua', [
            'username' => 'ibu_budi',
            'password' => 'salah',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_fails_when_username_not_registered(): void
    {
        $response = $this->postJson('/api/login/orangtua', [
            'username' => 'tidak_ada',
            'password' => 'apapun123',
        ]);

        $response->assertStatus(422);
    }

    public function test_orang_tua_account_is_not_auto_created_on_login(): void
    {
        $before = User::where('role', 'orang_tua')->count();

        $this->postJson('/api/login/orangtua', [
            'username' => 'belum_daftar',
            'password' => 'apapun123',
        ]);

        $this->assertEquals($before, User::where('role', 'orang_tua')->count());
    }
}
