<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Media\Database\Factories\MediaFactory;
use Modules\Media\Models\Media;
use Modules\Media\Support\PathGenerator\DefaultPathGenerator;
use Modules\Media\Support\PathGenerator\MediaPathGenerator;
use Modules\Media\Support\UrlGenerator\DefaultUrlGenerator;
use Modules\Media\Support\UrlGenerator\MediaUrlGenerator;
use Modules\Media\Tests\Support\CustomPrefixPathGenerator as SupportCustomPrefixPathGenerator;

covers(DefaultPathGenerator::class);
covers(DefaultUrlGenerator::class);

describe('Media generators config', function (): void {
    beforeEach(function (): void {
        Storage::fake('public');
        Storage::fake('local');
    });

    it('uses a custom path generator from config', function (): void {
        config(['media.path_generator' => CustomPrefixPathGenerator::class]);

        $media = MediaFactory::new()->public()->createOne();

        expect($media->getPath())->toBe('custom/'.$media->file_name);
    });

    it('uses a custom path generator only for the configured model', function (): void {
        $owner = loginAsUser();
        config(['media.custom_path_generators' => [
            $owner->getMorphClass() => CustomPrefixPathGenerator::class,
        ]]);

        $owned = MediaFactory::new()->forModel($owner)->createOne();
        $orphan = MediaFactory::new()->createOne();

        expect($owned->getPath())->toBe('custom/'.$owned->file_name)
            ->and($orphan->getPath())->toBe($orphan->collection_name.'/'.$orphan->file_name);
    });

    it('falls back to the default generator for invalid config classes', function (): void {
        config(['media.path_generator' => stdClass::class]);

        $media = MediaFactory::new()->createOne();

        expect($media->getPath())->toBe($media->collection_name.'/'.$media->file_name);
    });

    it('resolves derivative directories through the per-model path generator', function (): void {
        $owner = loginAsUser();
        config(['media.custom_path_generators' => [
            $owner->getMorphClass() => SupportCustomPrefixPathGenerator::class,
        ]]);

        $media = MediaFactory::new()->forModel($owner, 'avatars')->createOne(['file_name' => 'photo.webp']);

        expect($media->getConversionsDirectory())->toBe('custom-prefix/avatars/conversions/'.$media->id)
            ->and($media->getDerivedConversionsDirectory())->toBe('custom-prefix/avatars/conversions/derived/'.$media->id)
            ->and($media->getResponsiveImagesDirectory())->toBe('custom-prefix/avatars/responsive-images');
    });

    it('stores eager conversions inside the custom conversions directory', function (): void {
        config(['media.queue' => false]);

        DB::table('permissions')->insertOrIgnore([
            'id' => (string) Str::ulid(),
            'name' => PermissionEnum::MediaCreate->value,
            'guard_name' => 'sanctum',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = loginAsUser();
        $user->givePermissionTo(PermissionEnum::MediaCreate->value);
        config(['media.custom_path_generators' => [
            $user->getMorphClass() => SupportCustomPrefixPathGenerator::class,
        ]]);

        $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

        $response = $this->post('/api/v1/media', [
            'file' => $file,
            'collection_name' => 'avatars',
        ]);

        assertSuccessResponse($response, 201);

        $media = Media::query()->firstOrFail();

        expect($media->getPath())->toStartWith('custom-prefix/avatars/')
            ->and(Storage::disk($media->disk)->exists($media->getPath() ?? ''))->toBeTrue();

        $conversion = $media->conversions()->where('name', 'thumbnail')->firstOrFail();

        expect($conversion->path)->toStartWith('custom-prefix/avatars/conversions/'.$media->id.'/')
            ->and(Storage::disk('public')->exists($conversion->path))->toBeTrue();
    });

    it('removes replaced custom originals on single-file replacement', function (): void {
        $user = loginAsUser();
        config(['media.custom_path_generators' => [
            $user->getMorphClass() => SupportCustomPrefixPathGenerator::class,
        ]]);

        $first = $user->addMedia(UploadedFile::fake()->image('first.jpg', 100, 100))->toMediaCollection('avatars');
        $oldOriginal = $first->getPath();
        $oldConversion = $first->conversions()->where('name', 'thumbnail')->firstOrFail();

        $second = $user->addMedia(UploadedFile::fake()->image('second.jpg', 100, 100))->toMediaCollection('avatars');

        expect(Media::query()->where('model_id', $user->getKey())->where('collection_name', 'avatars')->count())->toBe(1)
            ->and($second->getPath())->toStartWith('custom-prefix/avatars/');

        Storage::disk($oldConversion->disk)->assertMissing($oldConversion->path);
        Storage::disk($second->disk)->assertMissing(is_string($oldOriginal) ? $oldOriginal : '');
    });

    it('stores non-image uploads inside the custom originals directory', function (): void {
        DB::table('permissions')->insertOrIgnore([
            'id' => (string) Str::ulid(),
            'name' => PermissionEnum::MediaCreate->value,
            'guard_name' => 'sanctum',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = loginAsUser();
        $user->givePermissionTo(PermissionEnum::MediaCreate->value);
        config(['media.custom_path_generators' => [
            $user->getMorphClass() => SupportCustomPrefixPathGenerator::class,
        ]]);

        $file = UploadedFile::fake()->createWithContent('notes.txt', 'hello world');

        $response = $this->post('/api/v1/media', [
            'file' => $file,
            'collection_name' => 'documents',
        ]);

        assertSuccessResponse($response, 201);

        $media = Media::query()->firstOrFail();

        expect($media->getPath())->toStartWith('custom-prefix/documents/')
            ->and(Storage::disk($media->disk)->exists($media->getPath() ?? ''))->toBeTrue();
    });
    it('stores on-demand derived conversions inside the custom derived directory', function (): void {
        $owner = loginAsUser();
        config(['media.custom_path_generators' => [
            $owner->getMorphClass() => SupportCustomPrefixPathGenerator::class,
        ]]);

        $media = MediaFactory::new()->forModel($owner)->createOne(['mime_type' => 'image/jpeg']);
        $file = UploadedFile::fake()->image('seed.jpg', 200, 100);
        Storage::disk('public')->put($media->getPath() ?? '', (string) $file->getContent());

        $this->getJson("/api/v1/media/{$media->id}/s/32")->assertOk();
        $this->getJson("/api/v1/media/{$media->id}/s/32")->assertOk();

        expect(Storage::disk('public')->allFiles('custom-prefix/default/conversions/derived/'.$media->id))->toHaveCount(1);
    });

    it('uses a custom url generator from config', function (): void {
        config(['media.url_generator' => FixedUrlGenerator::class]);

        $media = MediaFactory::new()->public()->createOne();
        $expiration = now()->addMinutes(15);

        expect($media->url())->toBe('https://cdn.example.test/'.$media->file_name)
            ->and($media->getTemporaryUrl($expiration))->toBe('https://cdn.example.test/'.$media->file_name.'?expires='.$expiration->timestamp)
            ->and(resolve(MediaUrlGenerator::class)->getUrl($media))->toBe('https://cdn.example.test/'.$media->file_name);
    });

    it('appends a version query string when version_urls is enabled', function (): void {
        config(['media.version_urls' => true]);

        $media = MediaFactory::new()->public()->createOne();

        expect($media->url() ?? '')->toMatch('/\?v=\d+$/');
    });

    it('omits the version query string by default', function (): void {
        $media = MediaFactory::new()->public()->createOne();

        expect($media->url() ?? '')->not->toContain('?v=');
    });

    it('uses the configured default lifetime for signed urls', function (): void {
        config(['media.temporary_url_default_lifetime' => 60]);

        $media = MediaFactory::new()->createOne();
        parse_str((string) parse_url($media->signedUrl(), PHP_URL_QUERY), $query);
        $expires = (int) ($query['expires'] ?? 0);

        expect($expires)->toBeGreaterThanOrEqual(now()->addMinutes(59)->timestamp)
            ->and($expires)->toBeLessThanOrEqual(now()->addMinutes(61)->timestamp);
    });
});

final class CustomPrefixPathGenerator implements MediaPathGenerator
{
    public function getPath(Media $media): string
    {
        return 'custom/'.$media->file_name;
    }

    public function getPathForConversions(Media $media): string
    {
        return 'custom/conversions/'.$media->id;
    }

    public function getPathForDerivedConversions(Media $media): string
    {
        return 'custom/conversions/derived/'.$media->id;
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return dirname($this->getPath($media)).'/responsive-images';
    }
}

final class FixedUrlGenerator implements MediaUrlGenerator
{
    public function getUrl(Media $media): string
    {
        return 'https://cdn.example.test/'.$media->file_name;
    }

    public function getTemporaryUrl(Media $media, DateTimeInterface $expiration): string
    {
        return 'https://cdn.example.test/'.$media->file_name.'?expires='.$expiration->getTimestamp();
    }

    public function getTemporaryUrlForMinutes(Media $media, int $ttlMinutes): string
    {
        return $this->getTemporaryUrl($media, now()->addMinutes($ttlMinutes));
    }
}
