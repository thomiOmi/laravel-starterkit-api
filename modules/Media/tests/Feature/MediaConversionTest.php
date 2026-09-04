<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Media\Database\Factories\MediaFactory;
use Modules\Media\Models\Media;

describe('Media conversions', function () {
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

    it('generates conversions synchronously on upload when queue is false', function () {
        config(['media.queue' => false]);

        $user = loginAsUser();
        $user->givePermissionTo(PermissionEnum::MediaCreate->value);

        $response = $this->post('/api/v1/media', [
            'file' => UploadedFile::fake()->image('photo.jpg', 100, 100),
            'collection_name' => 'avatars',
        ]);

        assertSuccessResponse($response, 201);

        $media = Media::query()->firstOrFail();
        expect($media->conversions()->count())->toBe(2);

        $conversion = $media->conversions()->where('name', 'thumbnail')->firstOrFail();
        expect($conversion->name)->toBe('thumbnail')
            ->and(Storage::disk('public')->exists($conversion->path))->toBeTrue()
            ->and($media->url('thumbnail'))->toBe(Storage::disk('public')->url($conversion->path));
    });

    it('returns null url for missing conversion', function () {
        $user = loginAsUser();
        $media = MediaFactory::new()->forModel($user)->createOne();

        expect($media->url('missing'))->toBeNull()
            ->and($media->hasGeneratedConversion('missing'))->toBeFalse();
    });
});
