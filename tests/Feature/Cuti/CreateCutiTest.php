<?php

namespace Tests\Feature\Cuti;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CreateCutiTest extends TestCase
{
    private function alamat(): array
    {
        return [
            'alamat_cuti' => [
                'jalan' => 'Jl. Merdeka No. 10',
                'rt_rw' => '001/002',
                'kelurahan' => 'Sukamaju',
                'kecamatan' => 'Cibinong',
                'kota' => 'Bogor',
                'provinsi' => 'Jawa Barat',
            ],
            'tujuan' => 'orang_tua',
        ];
    }

    public function test_taruna_can_create_cuti_with_transport_that_needs_no_ticket(): void
    {
        $taruna = User::factory()->create();

        $response = $this->actingAs($taruna, 'sanctum')->postJson('/api/cuti', $this->alamat() + [
            'tanggal_mulai' => now()->addDays(7)->toDateString(),
            'tanggal_selesai' => now()->addDays(9)->toDateString(),
            'transportasi' => 'pribadi',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('cuti_applications', [
            'taruna_id' => $taruna->id,
            'transportasi' => 'pribadi',
        ]);
    }

    public function test_cuti_fails_when_tanggal_mulai_is_in_the_past(): void
    {
        $taruna = User::factory()->create();

        $response = $this->actingAs($taruna, 'sanctum')->postJson('/api/cuti', $this->alamat() + [
            'tanggal_mulai' => now()->subDay()->toDateString(),
            'tanggal_selesai' => now()->addDay()->toDateString(),
            'transportasi' => 'pribadi',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['tanggal_mulai']);
    }

    public function test_cuti_fails_when_tanggal_selesai_before_tanggal_mulai(): void
    {
        $taruna = User::factory()->create();

        $response = $this->actingAs($taruna, 'sanctum')->postJson('/api/cuti', $this->alamat() + [
            'tanggal_mulai' => now()->addDays(5)->toDateString(),
            'tanggal_selesai' => now()->addDays(3)->toDateString(),
            'transportasi' => 'pribadi',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['tanggal_selesai']);
    }

    public function test_cuti_requires_ticket_upload_for_train_transport(): void
    {
        $taruna = User::factory()->create();

        $response = $this->actingAs($taruna, 'sanctum')->postJson('/api/cuti', $this->alamat() + [
            'tanggal_mulai' => now()->addDays(7)->toDateString(),
            'tanggal_selesai' => now()->addDays(9)->toDateString(),
            'transportasi' => 'kereta',
        ]);

        $response->assertStatus(422);
    }

    public function test_cuti_with_valid_ticket_upload_succeeds(): void
    {
        Storage::fake('public');
        $taruna = User::factory()->create();

        $response = $this->actingAs($taruna, 'sanctum')->post('/api/cuti', $this->alamat() + [
            'tanggal_mulai' => now()->addDays(7)->toDateString(),
            'tanggal_selesai' => now()->addDays(9)->toDateString(),
            'transportasi' => 'kereta',
            'tiket' => UploadedFile::fake()->create('tiket.pdf', 500, 'application/pdf'),
        ]);

        $response->assertStatus(201);
        $this->assertNotNull($response->json('data.tiket_path'));
    }

    public function test_cuti_rejects_invalid_ticket_mime_type(): void
    {
        Storage::fake('public');
        $taruna = User::factory()->create();

        $response = $this->actingAs($taruna, 'sanctum')->post('/api/cuti', $this->alamat() + [
            'tanggal_mulai' => now()->addDays(7)->toDateString(),
            'tanggal_selesai' => now()->addDays(9)->toDateString(),
            'transportasi' => 'kereta',
            'tiket' => UploadedFile::fake()->create('tiket.exe', 500, 'application/octet-stream'),
        ]);

        $response->assertStatus(422);
    }
}
