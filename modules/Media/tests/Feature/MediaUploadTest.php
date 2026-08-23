<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\IAM\Models\Permission;
use Modules\Media\Http\Controllers\V1\MediaUploadController;
use Modules\Media\Models\Media;

covers(MediaUploadController::class);

describe('POST /api/v1/media', function () {
    beforeEach(function () {
        Storage::fake('public');
        Permission::firstOrCreate(['name' => PermissionEnum::MediaCreate->value, 'guard_name' => 'sanctum']);
    });

    it('stores the upload and returns the created media', function () {
        $user = loginAsUser();
        $user->givePermissionTo(PermissionEnum::MediaCreate->value);

        $response = $this->post('/api/v1/media', [
            'file' => UploadedFile::fake()->create('photo.png', 20, 'image/png'),
            'collection_name' => 'avatars',
        ]);

        assertSuccessResponse($response, 201);
        expect($response->json('data.media.collection_name'))->toBe('avatars')
            ->and($response->json('data.media.original_name'))->toBe('photo.png')
            ->and($response->json('data.media.uploaded_by'))->toBe($user->id);

        $media = Media::query()->sole();
        expect($media->isOwnedBy($user->id))->toBeTrue();

        Storage::disk('public')->assertExists($media->path);
    });

    it('rejects invalid collection names', function (string $collection) {
        $user = loginAsUser();
        $user->givePermissionTo(PermissionEnum::MediaCreate->value);

        $response = $this->post('/api/v1/media', [
            'file' => UploadedFile::fake()->create('photo.png', 10, 'image/png'),
            'collection_name' => $collection,
        ]);

        assertProblemResponse($response, 422, 'validation');
        $response->assertJsonValidationErrors(['collection_name']);
    })->with([
        'with space' => 'my collection',
        'symbol' => 'col!ect',
    ]);

    it('defaults the collection name', function () {
        $user = loginAsUser();
        $user->givePermissionTo(PermissionEnum::MediaCreate->value);

        $response = $this->post('/api/v1/media', [
            'file' => UploadedFile::fake()->create('doc.png', 5, 'image/png'),
        ]);

        assertSuccessResponse($response, 201);
        expect($response->json('data.media.collection_name'))->toBe('default');
    });

    it('rejects disallowed extensions', function (string $filename) {
        $user = loginAsUser();
        $user->givePermissionTo(PermissionEnum::MediaCreate->value);

        $response = $this->post('/api/v1/media', [
            'file' => UploadedFile::fake()->create($filename, 10),
        ]);

        assertProblemResponse($response, 422, 'validation');
        $response->assertJsonValidationErrors(['file']);
    })->with([
        'script' => 'malware.exe',
        'text' => 'notes.txt',
    ]);

    it('rejects files above the size limit', function () {
        $user = loginAsUser();
        $user->givePermissionTo(PermissionEnum::MediaCreate->value);

        $response = $this->post('/api/v1/media', [
            'file' => UploadedFile::fake()->create('huge.png', 3000, 'image/png'),
        ]);

        assertProblemResponse($response, 422, 'validation');
        $response->assertJsonValidationErrors(['file']);
    });

    it('rejects unauthenticated requests', function () {
        $this->postJson('/api/v1/media')->assertUnauthorized();
    });

    it('rejects users without the create permission', function () {
        loginAsUser();

        $response = $this->post('/api/v1/media', [
            'file' => UploadedFile::fake()->create('photo.png', 10, 'image/png'),
        ]);

        assertProblemResponse($response, 403);
    });
});
