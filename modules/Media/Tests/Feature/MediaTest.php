<?php

declare(strict_types=1);

namespace Modules\Media\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

describe('Media Management', function () {
    beforeEach(function () {
        Storage::fake('public');
        $this->tenant = Tenant::create(['id' => 'test-tenant']);
        tenancy()->initialize($this->tenant);
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test', [], null, $this->tenant->id)->plainTextToken;
    });

    it('can upload media files', function () {
        $response = $this->postJson('/api/v1/media', [
            'file' => UploadedFile::fake()->image('avatar.jpg'),
            'collection' => 'avatars',
        ], [
            'X-Tenant' => $this->tenant->id,
            'Authorization' => 'Bearer '.$this->token,
        ]);

        $response->assertSuccessful()
            ->assertJsonPath('data.name', 'avatar')
            ->assertJsonPath('data.mime_type', 'image/jpeg');

        $this->assertDatabaseHas('media', [
            'tenant_id' => $this->tenant->id,
            'collection_name' => 'avatars',
        ]);
    });

    it('can list uploaded media', function () {
        $this->postJson('/api/v1/media', [
            'file' => UploadedFile::fake()->image('image1.jpg'),
        ], [
            'X-Tenant' => $this->tenant->id,
            'Authorization' => 'Bearer '.$this->token,
        ]);

        $response = $this->getJson('/api/v1/media', [
            'X-Tenant' => $this->tenant->id,
            'Authorization' => 'Bearer '.$this->token,
        ]);

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data');
    });

    it('can soft delete media', function () {
        $uploadResponse = $this->postJson('/api/v1/media', [
            'file' => UploadedFile::fake()->image('to_delete.jpg'),
        ], [
            'X-Tenant' => $this->tenant->id,
            'Authorization' => 'Bearer '.$this->token,
        ]);

        $mediaId = $uploadResponse->json('data.id');

        $this->deleteJson("/api/v1/media/{$mediaId}", [], [
            'X-Tenant' => $this->tenant->id,
            'Authorization' => 'Bearer '.$this->token,
        ])->assertSuccessful();

        $this->assertSoftDeleted('media', ['id' => $mediaId]);
    });
});
