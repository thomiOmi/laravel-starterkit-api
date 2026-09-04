<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Media\Models\Media;
use Modules\Media\Support\MediaPrefix;
use Modules\Media\Support\StorageOptions;

covers(MediaPrefix::class);
covers(StorageOptions::class);

describe('Media storage prefix', function () {
    beforeEach(function () {
        Storage::fake('public');
        DB::table('permissions')->insertOrIgnore([
            'id' => (string) Str::ulid(),
            'name' => PermissionEnum::MediaCreate->value,
            'guard_name' => 'sanctum',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    });

    it('builds paths without a prefix by default', function () {
        expect(MediaPrefix::basePath('avatars', 'photo.webp'))->toBe('avatars/photo.webp')
            ->and(MediaPrefix::directory('avatars'))->toBe('avatars')
            ->and(MediaPrefix::join('variants', 'abc', 'w32-hash.webp'))->toBe('variants/abc/w32-hash.webp');
    });

    it('merges only visibility by default for storage options', function () {
        expect(StorageOptions::forVisibility('public'))->toBe(['visibility' => 'public']);
    });

    it('merges configured remote headers into storage options', function () {
        config(['media.remote.extra_headers' => ['CacheControl' => 'max-age=604800']]);

        expect(StorageOptions::forVisibility('private'))->toBe([
            'visibility' => 'private',
            'CacheControl' => 'max-age=604800',
        ]);
    });

    it('stores and resolves uploads under the configured prefix', function () {
        config(['media.prefix' => 'tenant-a']);
        config(['media.mimes' => ['pdf']]);
        $user = loginAsUser();
        $user->givePermissionTo(PermissionEnum::MediaCreate->value);

        $response = $this->post('/api/v1/media', [
            'file' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
        ]);

        assertSuccessResponse($response, 201);

        $media = Media::query()->sole();

        expect($media->getPath())->toStartWith('tenant-a/default/');
        Storage::disk('public')->assertExists($media->getPath() ?? '');
    });

    it('stores conversions under the prefix with the conversions disk', function () {
        config(['media.prefix' => 'tenant-a']);
        config(['media.queue' => false]);
        $user = loginAsUser();
        $user->givePermissionTo(PermissionEnum::MediaCreate->value);

        $response = $this->post('/api/v1/media', [
            'file' => UploadedFile::fake()->image('photo.jpg', 100, 100),
            'collection_name' => 'avatars',
        ]);

        assertSuccessResponse($response, 201);

        $media = Media::query()->firstOrFail();
        $conversion = $media->conversions()->where('name', 'thumbnail')->firstOrFail();

        expect($conversion->path)->toStartWith('tenant-a/conversions/')
            ->and($media->conversions_disk)->toBe($media->disk);
        Storage::disk('public')->assertExists($conversion->path);
    });

    it('uses the configured conversions disk when set', function () {
        config(['media.conversions_disk_name' => 'public']);
        config(['media.mimes' => ['pdf']]);
        $user = loginAsUser();
        $user->givePermissionTo(PermissionEnum::MediaCreate->value);

        $this->post('/api/v1/media', [
            'file' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
        ]);

        expect(Media::query()->sole()->conversions_disk)->toBe('public');
    });
});
