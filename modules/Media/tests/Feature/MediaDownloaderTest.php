<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Support\Downloaders\DefaultDownloader;
use Modules\Media\Support\Downloaders\MediaDownloader;

covers(DefaultDownloader::class);

describe('Media downloader', function () {
    beforeEach(function () {
        Storage::fake('public');
    });

    it('downloads a remote file and attaches it with the url basename', function () {
        Http::fake([
            'https://example.com/image.jpg' => Http::response('fake-image-content', 200),
        ]);

        $owner = loginAsUser();

        $media = $owner->addMediaFromUrl('https://example.com/image.jpg')->toMediaCollection('default');

        expect($media->original_name)->toBe('image.jpg');
        Storage::disk('public')->assertExists($media->getPath() ?? '');
    });

    it('forwards custom headers to the remote request', function () {
        Http::fake([
            'https://example.com/private.jpg' => Http::response('content', 200),
        ]);

        $owner = loginAsUser();

        $owner->addMediaFromUrl('https://example.com/private.jpg', null, ['Authorization' => 'Bearer secret'])->toMediaCollection('default');

        Http::assertSent(fn (mixed $request): bool => $request instanceof Request && $request->hasHeader('Authorization', 'Bearer secret'));
    });

    it('throws for non-successful responses', function () {
        Http::fake([
            'https://example.com/missing.jpg' => Http::response('nope', 404),
        ]);

        $owner = loginAsUser();

        expect(fn (): mixed => $owner->addMediaFromUrl('https://example.com/missing.jpg')->toMediaCollection('default'))
            ->toThrow(InvalidArgumentException::class, 'Failed to fetch URL');
    });

    it('throws for connection failures', function () {
        Http::fake([
            'https://example.com/*' => Http::failedConnection(),
        ]);

        $owner = loginAsUser();

        expect(fn (): mixed => $owner->addMediaFromUrl('https://example.com/down.jpg')->toMediaCollection('default'))
            ->toThrow(InvalidArgumentException::class, 'Failed to fetch URL');
    });

    it('throws for empty bodies', function () {
        Http::fake([
            'https://example.com/empty.jpg' => Http::response('', 200),
        ]);

        $owner = loginAsUser();

        expect(fn (): mixed => $owner->addMediaFromUrl('https://example.com/empty.jpg')->toMediaCollection('default'))
            ->toThrow(InvalidArgumentException::class, 'empty body');
    });

    it('uses a custom downloader from config', function () {
        config(['media.media_downloader' => FixedContentDownloader::class]);

        $owner = loginAsUser();

        $media = $owner->addMediaFromUrl('https://example.com/ignored.jpg', 'custom-name.jpg')->toMediaCollection('default');

        expect($media->original_name)->toBe('custom-name.jpg');
    });
});

final class FixedContentDownloader implements MediaDownloader
{
    public function download(string $url, array $headers = []): array
    {
        return ['content' => 'fixed-content', 'filename' => basename((string) parse_url($url, PHP_URL_PATH))];
    }
}
