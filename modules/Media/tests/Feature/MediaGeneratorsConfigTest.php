<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;
use Modules\Media\Database\Factories\MediaFactory;
use Modules\Media\Models\Media;
use Modules\Media\Support\PathGenerator\DefaultPathGenerator;
use Modules\Media\Support\PathGenerator\MediaPathGenerator;
use Modules\Media\Support\UrlGenerator\DefaultUrlGenerator;
use Modules\Media\Support\UrlGenerator\MediaUrlGenerator;

covers(DefaultPathGenerator::class);
covers(DefaultUrlGenerator::class);

describe('Media generators config', function () {
    beforeEach(function () {
        Storage::fake('public');
    });

    it('uses a custom path generator from config', function () {
        config(['media.path_generator' => CustomPrefixPathGenerator::class]);

        $media = MediaFactory::new()->public()->createOne();

        expect($media->getPath())->toBe('custom/'.$media->file_name);
    });

    it('uses a custom path generator only for the configured model', function () {
        $owner = loginAsUser();
        config(['media.custom_path_generators' => [
            $owner->getMorphClass() => CustomPrefixPathGenerator::class,
        ]]);

        $owned = MediaFactory::new()->forModel($owner)->createOne();
        $orphan = MediaFactory::new()->createOne();

        expect($owned->getPath())->toBe('custom/'.$owned->file_name)
            ->and($orphan->getPath())->toBe($orphan->collection_name.'/'.$orphan->file_name);
    });

    it('falls back to the default generator for invalid config classes', function () {
        config(['media.path_generator' => stdClass::class]);

        $media = MediaFactory::new()->createOne();

        expect($media->getPath())->toBe($media->collection_name.'/'.$media->file_name);
    });

    it('uses a custom url generator from config', function () {
        config(['media.url_generator' => FixedUrlGenerator::class]);

        $media = MediaFactory::new()->public()->createOne();

        expect(app(MediaUrlGenerator::class)->getUrl($media))->toBe('https://cdn.example.test/'.$media->file_name);
    });

    it('appends a version query string when version_urls is enabled', function () {
        config(['media.version_urls' => true]);

        $media = MediaFactory::new()->public()->createOne();

        expect($media->url() ?? '')->toMatch('/\?v=\d+$/');
    });

    it('omits the version query string by default', function () {
        $media = MediaFactory::new()->public()->createOne();

        expect($media->url() ?? '')->not->toContain('?v=');
    });

    it('uses the configured default lifetime for signed urls', function () {
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

    public function getPathForConversions(Media $media, string $conversion): string
    {
        return 'custom/conversions/'.$conversion.'/'.$media->file_name;
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return 'custom/responsive-images/'.$media->file_name;
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
