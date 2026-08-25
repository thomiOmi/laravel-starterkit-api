<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Modules\IAM\Models\Permission;
use Modules\Media\Events\MediaUploaded;
use Modules\Media\Http\Controllers\V1\MediaUploadController;
use Modules\Media\Models\Media;

covers(MediaUploadController::class);

describe('POST /api/v1/media', function () {
    beforeEach(function () {
        Storage::fake('public');
        Permission::firstOrCreate(['name' => PermissionEnum::MediaCreate->value, 'guard_name' => 'sanctum']);
    });

    it('stores the upload and returns the created media', function () {
        Event::fake([MediaUploaded::class]);
        $user = loginAsUser();
        $user->givePermissionTo(PermissionEnum::MediaCreate->value);

        $response = $this->post('/api/v1/media', [
            'file' => UploadedFile::fake()->image('photo.png', 64, 48),
            'collection_name' => 'avatars',
        ]);

        assertSuccessResponse($response, 201);
        expect($response->json('data.media.collection_name'))->toBe('avatars')
            ->and($response->json('data.media.original_name'))->toBe('photo.png')
            ->and($response->json('data.media.uploaded_by'))->toBe($user->id)
            ->and($response->json('data.url'))->toEndWith('.webp');

        $media = Media::query()->sole();
        $meta = is_array($media->meta) ? $media->meta : [];
        expect($media->isOwnedBy($user->id))->toBeTrue()
            ->and($media->mime_type)->toBe('image/webp')
            ->and($meta)->toHaveKey('original_name', 'photo.png')
            ->and($meta['width'] ?? null)->toBeInt()
            ->and($meta['width'] ?? 0)->toBeGreaterThan(0)
            ->and($meta['height'] ?? null)->toBeInt()
            ->and($meta['height'] ?? 0)->toBeGreaterThan(0);

        Storage::disk('public')->assertExists($media->path);
        Event::assertDispatched(MediaUploaded::class, fn (MediaUploaded $event): bool => $event->media->is($media));
    });

    it('normalizes decodable images to webp regardless of source format', function () {
        $user = loginAsUser();
        $user->givePermissionTo(PermissionEnum::MediaCreate->value);

        $response = $this->post('/api/v1/media', [
            'file' => UploadedFile::fake()->image('shot.jpg', 100, 80),
        ]);

        assertSuccessResponse($response, 201);
        $media = Media::query()->sole();
        expect($media->mime_type)->toBe('image/webp')
            ->and($media->path)->toEndWith('.webp');
        Storage::disk('public')->assertExists($media->path);
    });

    it('stores non-image files untouched when their extension is allowed', function () {
        config(['media.mimes' => ['pdf']]);
        $user = loginAsUser();
        $user->givePermissionTo(PermissionEnum::MediaCreate->value);

        $response = $this->post('/api/v1/media', [
            'file' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
        ]);

        assertSuccessResponse($response, 201);
        $media = Media::query()->sole();
        expect($media->mime_type)->toBe('application/pdf')
            ->and($media->path)->toEndWith('.pdf')
            ->and($media->meta)->toHaveKey('original_name')
            ->and($media->meta)->not->toHaveKey('width');
        Storage::disk('public')->assertExists($media->path);
    });

    it('rejects invalid collection names', function (string $collection) {
        $user = loginAsUser();
        $user->givePermissionTo(PermissionEnum::MediaCreate->value);

        $response = $this->post('/api/v1/media', [
            'file' => UploadedFile::fake()->image('photo.png', 32, 32),
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
            'file' => UploadedFile::fake()->image('doc.png', 16, 16),
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
