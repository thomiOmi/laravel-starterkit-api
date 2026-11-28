<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Http\Controllers\V1\MediaUploadController;
use Modules\Media\Models\Media;

covers(MediaUploadController::class);

describe('animated image uploads', function (): void {
    beforeEach(function (): void {
        Storage::fake('public');
        Storage::fake('local');
        ensurePermissionExists(PermissionEnum::MediaCreate->value);
        $user = loginAsUser();
        $user->givePermissionTo(PermissionEnum::MediaCreate->value);
    });

    function singleFrameGif(): string
    {
        return (string) UploadedFile::fake()->image('still.gif', 32, 32)->getContent();
    }

    function twoFrameGif(): string
    {
        $base64 = 'R0lGODlhIAAgAPcfMQAAACQAAEgAAGwAAJAAALQAANgAAPwAAAAkACQkAEgkAGwkAJAkALQkANgkAPwkAABIACRIAEhIAGxIAJBIALRIANhIAPxIAABsACRsAEhsAGxsAJBsALRsANhsAPxsAACQACSQAEiQAGyQAJCQALSQANiQAPyQAAC0ACS0AEi0AGy0AJC0ALS0ANi0APy0AADYACTYAEjYAGzYAJDYALTYANjYAPzYAAD8ACT8AEj8AGz8AJD8ALT8ANj8APz8AAAAVSQAVUgAVWwAVZAAVbQAVdgAVfwAVQAkVSQkVUgkVWwkVZAkVbQkVdgkVfwkVQBIVSRIVUhIVWxIVZBIVbRIVdhIVfxIVQBsVSRsVUhsVWxsVZBsVbRsVdhsVfxsVQCQVSSQVUiQVWyQVZCQVbSQVdiQVfyQVQC0VSS0VUi0VWy0VZC0VbS0Vdi0Vfy0VQDYVSTYVUjYVWzYVZDYVbTYVdjYVfzYVQD8VST8VUj8VWz8VZD8VbT8Vdj8Vfz8VQAAqiQAqkgAqmwAqpAAqrQAqtgAqvwAqgAkqiQkqkgkqmwkqpAkqrQkqtgkqvwkqgBIqiRIqkhIqmxIqpBIqrRIqthIqvxIqgBsqiRsqkhsqmxsqpBsqrRsqthsqvxsqgCQqiSQqkiQqmyQqpCQqrSQqtiQqvyQqgC0qiS0qki0qmy0qpC0qrS0qti0qvy0qgDYqiTYqkjYqmzYqpDYqrTYqtjYqvzYqgD8qiT8qkj8qmz8qpD8qrT8qtj8qvz8qgAA/yQA/0gA/2wA/5AA/7QA/9gA//wA/wAk/yQk/0gk/2wk/5Ak/7Qk/9gk//wk/wBI/yRI/0hI/2xI/5BI/7RI/9hI//xI/wBs/yRs/0hs/2xs/5Bs/7Rs/9hs//xs/wCQ/ySQ/0iQ/2yQ/5CQ/7SQ/9iQ//yQ/wC0/yS0/0i0/2y0/5C0/7S0/9i0//y0/wDY/yTY/0jY/2zY/5DY/7TY/9jY//zY/wD8/yT8/0j8/2z8/5D8/7T8/9j8//z8/yH/C05FVFNDQVBFMi4wAwEAAAAh+QQEBAAfACwAAAAAIAAgAAAIWQAPCBxIsKDBgwgTKlzIsGFBAwchGpT40KHFixgzakRIkWDHgR8FhtxIsqTJiCgnpjzJsqVLkCs9xnxJsybHmSJx2tzJs6LKnz57Cn05sqjOoUhdGgUqU2BAACH5BAUEAAAALB8AHwABAAEAAAgEAAEEBAAh+QQFBAAAACwfAB8AAQABAAAIBAABBAQAIfkEBQQAAAAsHwAfAAEAAQAACAQAAQQEACH5BAUEAAAALB8AHwABAAEAAAgEAAEEBAAh+QQFBAAAACwAAAAAIAAgAAAIWAABARtIEJjAggMPIlSIsKHDhxAjSpxIsSJEhgQxJnSo0aLHjyBDPuxIkqPIkyhTnizZkKXKlzBRuiw4M6bNmyFrbmyJs6dPnkAXmvxJtCjNoUKDGl2aMiAAIfkEBQQAAAAsHwAfAAEAAQAACAQAAQQEACH5BAUEAAAALB8AHwABAAEAAAgEAAEEBAAh+QQFBAAAACwfAB8AAQABAAAIBAABBAQAIfkEBQQAAAAsHwAfAAEAAQAACAQAAQQEADs=';

        return base64_decode($base64, true);
    }

    function gifWithActl(string $png): string
    {
        $ihdrLength = unpack('N', substr($png, 8, 4));
        throw_unless(is_array($ihdrLength), RuntimeException::class, 'PNG IHDR length was missing.');

        $ihdrSize = $ihdrLength[1] ?? null;
        throw_unless(is_int($ihdrSize), RuntimeException::class, 'PNG IHDR length was not an integer.');

        $afterIhdr = 8 + 12 + $ihdrSize;
        $data = pack('NNN', 2, 0, 0);
        $chunk = pack('N', strlen($data)).'acTL'.$data.pack('N', crc32('acTL'.$data));

        return substr($png, 0, $afterIhdr).$chunk.substr($png, $afterIhdr);
    }

    function animatedWebp(): string
    {
        return (string) hex2bin('524946461a000000574542505650384c080000000100000001000000c9000000');
    }

    it('stores an animated gif untouched and byte-identical', function (): void {
        $bytes = twoFrameGif();
        $response = $this->post('/api/v1/media', [
            'file' => UploadedFile::fake()->createWithContent('anim.gif', $bytes),
            'collection_name' => 'default',
        ]);

        assertSuccessResponse($response, 201);
        $media = Media::query()->sole();

        expect($media->mime_type)->toBe('image/gif')
            ->and($media->file_name)->toEndWith('.gif')
            ->and(Storage::disk($media->disk)->get($media->getPath() ?? ''))->toBe($bytes);
    });

    it('still re-encodes a single frame gif to webp', function (): void {
        $response = $this->post('/api/v1/media', [
            'file' => UploadedFile::fake()->createWithContent('still.gif', singleFrameGif()),
            'collection_name' => 'default',
        ]);

        assertSuccessResponse($response, 201);
        $media = Media::query()->sole();
        expect($media->mime_type)->toBe('image/webp')
            ->and($media->file_name)->toEndWith('.webp');
    });

    it('stores an apng with acTL untouched', function (): void {
        $bytes = gifWithActl((string) UploadedFile::fake()->image('still.png', 32, 32)->getContent());
        $response = $this->post('/api/v1/media', [
            'file' => UploadedFile::fake()->createWithContent('anim.png', $bytes),
            'collection_name' => 'default',
        ]);

        assertSuccessResponse($response, 201);
        $media = Media::query()->sole();
        expect($media->file_name)->toEndWith('.png')
            ->and(Storage::disk($media->disk)->get($media->getPath() ?? ''))->toBe($bytes);
    });

    it('still re-encodes a static png', function (): void {
        $response = $this->post('/api/v1/media', [
            'file' => UploadedFile::fake()->image('still.png', 32, 32),
            'collection_name' => 'default',
        ]);

        assertSuccessResponse($response, 201);
        expect(Media::query()->sole()->file_name)->toEndWith('.webp');
    });

    it('does not crash or hang on a truncated gif', function (): void {
        $response = $this->post('/api/v1/media', [
            'file' => UploadedFile::fake()->createWithContent('broken.gif', substr(twoFrameGif(), 0, 30)),
            'collection_name' => 'default',
        ]);

        assertSuccessResponse($response, 201);
        expect(Media::query()->count())->toBe(1);
    });

    it('serves a gif php polyglot with a hashed name and nosniff', function (): void {
        $bytes = twoFrameGif()."<?php echo 1; ?>\n";
        $response = $this->post('/api/v1/media', [
            'file' => UploadedFile::fake()->createWithContent('polyglot.gif', $bytes),
            'collection_name' => 'default',
        ]);

        assertSuccessResponse($response, 201);
        $media = Media::query()->sole();

        expect($media->file_name)->toEndWith('.gif')
            ->and($media->original_name)->toBe('polyglot.gif');

        $download = $this->get($media->signedUrl(10));
        $download->assertOk();
        expect($download->headers->get('X-Content-Type-Options'))->toBe('nosniff')
            ->and($download->streamedContent())->toBe($bytes);
    });

    it('keeps an animated webp intact', function (): void {
        $bytes = animatedWebp();
        $response = $this->post('/api/v1/media', [
            'file' => UploadedFile::fake()->createWithContent('anim.webp', $bytes),
            'collection_name' => 'default',
        ]);

        assertSuccessResponse($response, 201);
        $media = Media::query()->sole();
        expect($media->file_name)->toEndWith('.webp')
            ->and(Storage::disk($media->disk)->get($media->getPath() ?? ''))->toBe($bytes);
    });

    it('still applies the image size limit to animated files', function (): void {
        $bytes = twoFrameGif().str_repeat("\0", 2049 * 1024);
        $response = $this->post('/api/v1/media', [
            'file' => UploadedFile::fake()->createWithContent('large.gif', $bytes),
            'collection_name' => 'default',
        ]);

        assertProblemResponse($response, 422);
    });
});
