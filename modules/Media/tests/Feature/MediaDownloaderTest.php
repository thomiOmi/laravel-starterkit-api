<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\Media;
use Modules\Media\Support\Downloaders\DefaultDownloader;
use Modules\Media\Support\MediaMimeType;
use Modules\Media\Tests\Support\FixedContentDownloader;

covers(DefaultDownloader::class);

describe('Media downloader', function (): void {
    beforeEach(function (): void {
        Storage::fake('public');
        Storage::fake('local');
    });

    it('downloads a remote file and attaches it with the url basename', function (): void {
        $jpeg = (string) UploadedFile::fake()->image('seed.jpg', 20, 20)->getContent();

        Http::fake([
            'https://example.com/image.jpg' => Http::response($jpeg, 200),
        ]);

        $owner = loginAsUser();

        $media = $owner->addMediaFromUrl('https://example.com/image.jpg')->toMediaCollection('default');

        expect($media->original_name)->toBe('image.jpg');
        Storage::disk($media->disk)->assertExists($media->getPath() ?? '');
        // The upload pipeline transcodes the fetched image, so only the decoded
        // mime is stable; the raw remote bytes are deliberately not preserved.
        $stored = Storage::disk($media->disk)->get($media->getPath() ?? '');
        throw_unless(is_string($stored), RuntimeException::class, 'Stored file was not readable.');

        expect(MediaMimeType::detectFromContent($stored))->toStartWith('image/');
    });

    it('rejects remote bodies that sniff as blocked content', function (string $body): void {
        Http::fake(['example.com/*' => Http::response($body, 200)]);

        $owner = loginAsUser();

        expect(fn (): mixed => $owner->addMediaFromUrl('https://example.com/payload.jpg')->toMediaCollection('default'))
            ->toThrow(InvalidArgumentException::class, 'Failed to fetch remote file.')
            ->and(Media::query()->count())->toBe(0);
    })->with([
        'php payload named .jpg' => ['<?php echo "pwned"; ?>'],
        'svg payload' => ['<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'],
        'html payload' => ['<html><body><p>hi</p></body></html>'],
    ]);

    it('rejects an oversized download declared by content length', function (): void {
        config(['media.max_size' => 1]);
        $body = str_repeat('a', 2048);

        Http::fake(['example.com/*' => Http::response($body, 200, ['Content-Length' => (string) strlen($body)])]);

        $owner = loginAsUser();

        expect(fn (): mixed => $owner->addMediaFromUrl('https://example.com/large.jpg')->toMediaCollection('default'))
            ->toThrow(InvalidArgumentException::class, 'Failed to fetch remote file.')
            ->and(Media::query()->count())->toBe(0);
    });

    it('rejects an oversized download caught while streaming', function (): void {
        config(['media.max_size' => 1]);
        $body = str_repeat('a', 2048);

        Http::fake(['example.com/*' => Http::response($body, 200)]);

        $owner = loginAsUser();

        expect(fn (): mixed => $owner->addMediaFromUrl('https://example.com/large.jpg')->toMediaCollection('default'))
            ->toThrow(InvalidArgumentException::class, 'Failed to fetch remote file.')
            ->and(Media::query()->count())->toBe(0);
    });

    it('forwards custom headers to the remote request', function (): void {
        $jpeg = (string) UploadedFile::fake()->image('seed.jpg', 20, 20)->getContent();

        Http::fake([
            'https://example.com/private.jpg' => Http::response($jpeg, 200),
        ]);

        $owner = loginAsUser();

        $owner->addMediaFromUrl('https://example.com/private.jpg', null, ['Authorization' => 'Bearer secret'])->toMediaCollection('default');

        Http::assertSent(fn (mixed $request): bool => $request instanceof Request && $request->hasHeader('Authorization', 'Bearer secret'));
    });

    it('throws for non-successful responses', function (): void {
        Http::fake([
            'https://example.com/missing.jpg' => Http::response('nope', 404),
        ]);

        $owner = loginAsUser();

        expect(fn (): mixed => $owner->addMediaFromUrl('https://example.com/missing.jpg')->toMediaCollection('default'))
            ->toThrow(InvalidArgumentException::class, 'Failed to fetch remote file.');
    });

    it('throws for connection failures', function (): void {
        Http::fake([
            'https://example.com/*' => Http::failedConnection(),
        ]);

        $owner = loginAsUser();

        expect(fn (): mixed => $owner->addMediaFromUrl('https://example.com/down.jpg')->toMediaCollection('default'))
            ->toThrow(InvalidArgumentException::class, 'Failed to fetch remote file.');
    });

    it('throws for empty bodies', function (): void {
        Http::fake([
            'https://example.com/empty.jpg' => Http::response('', 200),
        ]);

        $owner = loginAsUser();

        expect(fn (): mixed => $owner->addMediaFromUrl('https://example.com/empty.jpg')->toMediaCollection('default'))
            ->toThrow(InvalidArgumentException::class, 'Failed to fetch remote file.');
    });

    it('rejects redirects instead of following an unvalidated destination', function (): void {
        Http::fake([
            'https://example.com/redirect.jpg' => Http::response('', 302, ['Location' => 'https://127.0.0.1/private.jpg']),
        ]);

        $owner = loginAsUser();

        expect(fn (): mixed => $owner->addMediaFromUrl('https://example.com/redirect.jpg')->toMediaCollection('default'))
            ->toThrow(InvalidArgumentException::class, 'Failed to fetch remote file.');
    });

    it('rejects plain http urls by default', function (): void {
        $owner = loginAsUser();

        expect(fn (): mixed => $owner->addMediaFromUrl('http://example.com/image.jpg')->toMediaCollection('default'))
            ->toThrow(InvalidArgumentException::class, 'Failed to fetch remote file.');
    });

    it('rejects private IP literals without network access', function (): void {
        $owner = loginAsUser();

        expect(fn (): mixed => $owner->addMediaFromUrl('https://127.0.0.1/image.jpg')->toMediaCollection('default'))
            ->toThrow(InvalidArgumentException::class, 'Failed to fetch remote file.');
    });

    it('rejects localhost hostnames', function (): void {
        $owner = loginAsUser();

        expect(fn (): mixed => $owner->addMediaFromUrl('https://localhost/image.jpg')->toMediaCollection('default'))
            ->toThrow(InvalidArgumentException::class, 'Failed to fetch remote file.');
    });

    it('uses a custom downloader from config', function (): void {
        config(['media.media_downloader' => FixedContentDownloader::class]);

        $owner = loginAsUser();

        $media = $owner->addMediaFromUrl('https://example.com/ignored.jpg', 'custom-name.jpg')->toMediaCollection('default');

        expect($media->original_name)->toBe('custom-name.jpg');
    });
});
