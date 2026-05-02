<?php

declare(strict_types=1);

namespace Modules\Media\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Tests\TestCase;

class MediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_user_can_upload_media(): void
    {
        $tenant = Tenant::create(['id' => 'test-tenant']);
        tenancy()->initialize($tenant);

        $user = User::factory()->create();
        $token = $user->createToken('test', [], null, $tenant->id)->plainTextToken;

        $response = $this->postJson('/api/v1/media', [
            'file' => UploadedFile::fake()->image('avatar.jpg'),
            'collection' => 'avatars',
        ], [
            'X-Tenant' => $tenant->id,
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'avatar')
            ->assertJsonPath('data.mime_type', 'image/jpeg');

        $this->assertDatabaseHas('media', [
            'tenant_id' => $tenant->id,
            'collection_name' => 'avatars',
        ]);
    }

    public function test_user_can_list_media(): void
    {
        $tenant = Tenant::create(['id' => 'test-tenant']);
        tenancy()->initialize($tenant);

        $user = User::factory()->create();
        $token = $user->createToken('test', [], null, $tenant->id)->plainTextToken;

        // Upload first
        $this->postJson('/api/v1/media', [
            'file' => UploadedFile::fake()->image('image1.jpg'),
        ], [
            'X-Tenant' => $tenant->id,
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response = $this->getJson('/api/v1/media', [
            'X-Tenant' => $tenant->id,
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_user_can_delete_media(): void
    {
        $tenant = Tenant::create(['id' => 'test-tenant']);
        tenancy()->initialize($tenant);

        $user = User::factory()->create();
        $token = $user->createToken('test', [], null, $tenant->id)->plainTextToken;

        $uploadResponse = $this->postJson('/api/v1/media', [
            'file' => UploadedFile::fake()->image('to_delete.jpg'),
        ], [
            'X-Tenant' => $tenant->id,
            'Authorization' => 'Bearer ' . $token,
        ]);

        $mediaId = $uploadResponse->json('data.id');

        $response = $this->deleteJson("/api/v1/media/{$mediaId}", [], [
            'X-Tenant' => $tenant->id,
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200);
        $this->assertSoftDeleted('media', ['id' => $mediaId]);
    }
}
