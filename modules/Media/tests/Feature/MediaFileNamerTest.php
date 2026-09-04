<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Media\Models\Media;
use Modules\Media\Support\FileNamer\DefaultFileNamer;
use Modules\Media\Support\FileNamer\MediaFileNamer;

covers(DefaultFileNamer::class);

describe('Media file namer', function () {
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

    it('returns the input unchanged with the default namer', function () {
        $namer = new DefaultFileNamer;

        expect($namer->originalFileName('abc123.png'))->toBe('abc123.png')
            ->and($namer->conversionFileName('abc123.webp', 'thumbnail'))->toBe('abc123-thumbnail.webp')
            ->and($namer->responsiveFileName('abc123.webp'))->toBe('abc123.webp');
    });

    it('keeps hash-based paths by default on upload', function () {
        config(['media.mimes' => ['pdf']]);
        $user = loginAsUser();
        $user->givePermissionTo(PermissionEnum::MediaCreate->value);

        $response = $this->post('/api/v1/media', [
            'file' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
        ]);

        assertSuccessResponse($response, 201);

        $media = Media::query()->sole();

        expect($media->file_name)->toEndWith('.pdf');
        Storage::disk('public')->assertExists($media->getPath() ?? '');
    });

    it('uses a custom namer from config for originals and conversions', function () {
        config(['media.file_namer' => CustomPrefixFileNamer::class]);
        config(['media.queue' => false]);

        $user = loginAsUser();
        $user->givePermissionTo(PermissionEnum::MediaCreate->value);

        $response = $this->post('/api/v1/media', [
            'file' => UploadedFile::fake()->image('photo.jpg', 100, 100),
            'collection_name' => 'avatars',
        ]);

        assertSuccessResponse($response, 201);

        $media = Media::query()->firstOrFail();

        expect($media->file_name)->toStartWith('custom-');
        Storage::disk('public')->assertExists($media->getPath() ?? '');

        $conversion = $media->conversions()->where('name', 'thumbnail')->firstOrFail();

        expect($conversion->path)->toContain('custom-')
            ->and($conversion->path)->toContain('-thumbnail');
        Storage::disk('public')->assertExists($conversion->path);
    });

    it('lets an explicit usingFileName win over the namer for the original name', function () {
        config(['media.file_namer' => CustomPrefixFileNamer::class]);
        config(['media.mimes' => ['pdf']]);

        $user = loginAsUser();

        $media = $user->addMedia(UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'))
            ->usingFileName('explicit.pdf')
            ->toMediaCollection('documents');

        expect($media->original_name)->toBe('explicit.pdf')
            ->and($media->file_name)->toStartWith('custom-');
    });
});

final class CustomPrefixFileNamer implements MediaFileNamer
{
    public function originalFileName(string $fileName): string
    {
        return 'custom-'.$fileName;
    }

    public function conversionFileName(string $fileName, string $conversion): string
    {
        $name = pathinfo($fileName, PATHINFO_FILENAME);
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);

        $converted = 'custom-'.$name.'-'.$conversion;

        return $extension !== '' ? $converted.'.'.$extension : $converted;
    }

    public function responsiveFileName(string $fileName): string
    {
        return $fileName;
    }
}
