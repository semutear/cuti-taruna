<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\TestCase;

class LoginOrangTuaTest extends TestCase
{
    public function test_orang_tua_can_login_with_child_data_and_account_is_auto_created(): void
    {
        $anak = User::factory()->create([
            'nama_lengkap' => 'Budi Santoso',
            'nama_ibu' => 'Siti Aminah',
            'tanggal_lahir' => '2005-01-01',
        ]);

        $before = User::where('role', 'orang_tua')->count();

        $response = $this->postJson('/api/login/orangtua', [
            'nama_ibu' => 'Siti Aminah',
            'nama_anak' => 'Budi Santoso',
            'tanggal_lahir_anak' => '2005-01-01',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.anak.id', $anak->id);

        // Perilaku lama (sengaja dipertahankan): akun ortu otomatis dibuat
        $this->assertEquals($before + 1, User::where('role', 'orang_tua')->count());
    }

    public function test_login_fails_when_child_data_does_not_match(): void
    {
        User::factory()->create([
            'nama_lengkap' => 'Budi Santoso',
            'nama_ibu' => 'Siti Aminah',
            'tanggal_lahir' => '2005-01-01',
        ]);

        $response = $this->postJson('/api/login/orangtua', [
            'nama_ibu' => 'Nama Salah',
            'nama_anak' => 'Budi Santoso',
            'tanggal_lahir_anak' => '2005-01-01',
        ]);

        $response->assertStatus(404);
    }

    public function test_repeated_login_reuses_the_same_orang_tua_account(): void
    {
        User::factory()->create([
            'nama_lengkap' => 'Budi Santoso',
            'nama_ibu' => 'Siti Aminah',
            'tanggal_lahir' => '2005-01-01',
        ]);

        $payload = [
            'nama_ibu' => 'Siti Aminah',
            'nama_anak' => 'Budi Santoso',
            'tanggal_lahir_anak' => '2005-01-01',
        ];

        $this->postJson('/api/login/orangtua', $payload);
        $this->postJson('/api/login/orangtua', $payload);

        $this->assertEquals(1, User::where('role', 'orang_tua')->count());
    }
}
