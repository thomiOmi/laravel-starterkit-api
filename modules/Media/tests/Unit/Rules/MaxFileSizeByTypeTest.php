<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Modules\Media\Rules\MaxFileSizeByType;

covers(MaxFileSizeByType::class);

/**
 * Build an uploaded file whose content sniffing yields the given MIME prefix.
 */
function sizedUpload(string $name, string $header, int $kilobytes): UploadedFile
{
    $content = $header.str_repeat('x', max(0, ($kilobytes * 1024) - strlen($header)));

    return UploadedFile::fake()->createWithContent($name, $content);
}

/**
 * @return list<string>
 */
function failuresFor(UploadedFile $file): array
{
    $messages = Validator::make(['file' => $file], ['file' => [new MaxFileSizeByType]])
        ->errors()
        ->get('file');

    $failures = [];

    foreach ($messages as $message) {
        if (is_array($message)) {
            foreach ($message as $nested) {
                $failures[] = $nested;
            }

            continue;
        }

        $failures[] = $message;
    }

    return $failures;
}

it('ships the approved per-type defaults', function (): void {
    expect(config('media.max_size_by_type'))->toBe([
        'image' => 2048,
        'document' => 10240,
        'audio' => 20480,
        'video' => 51200,
    ]);
});

describe('MaxFileSizeByType', function (): void {
    beforeEach(function (): void {
        config()->set('media.max_size', 2048);
        config()->set('media.max_size_by_type', [
            'image' => 2048,
            'document' => 10240,
            'audio' => 20480,
            'video' => 51200,
        ]);
    });

    it('uses the per-type limit instead of the global default', function (): void {
        $file = sizedUpload('clip.mp4', "\x00\x00\x00\x18ftypisom\x00\x00\x02\x00isomiso2", 3000);

        expect(failuresFor($file))->toBeEmpty();
    });

    it('falls back to the global limit for a type without an entry', function (): void {
        $file = sizedUpload('bundle.bin', str_repeat("\x00", 16), 3000);

        expect(failuresFor($file))->not->toBeEmpty();
    });

    it('falls back to the global limit when detection fails', function (): void {
        $file = Mockery::mock(UploadedFile::class);
        $file->shouldReceive('isValid')->andReturn(true);
        $file->shouldReceive('getRealPath')->once()->andReturn(false);
        $file->shouldReceive('getSize')->once()->andReturn(3000 * 1024);

        expect(failuresFor($file))->not->toBeEmpty();
    });

    it('ignores invalid config values without throwing', function (mixed $byType, mixed $global): void {
        config()->set('media.max_size_by_type', $byType);
        config()->set('media.max_size', $global);

        $file = sizedUpload('photo.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFAAH/q842iQAAAABJRU5ErkJggg=='), 10);

        expect(failuresFor($file))->toBeEmpty();
    })->with([
        'map is not an array' => ['nope', 2048],
        'per-type value is negative' => [['image' => -5], 2048],
        'global value is a string' => [['image' => 2048], 'nope'],
    ]);

    it('accepts exactly the limit and rejects one kilobyte over with the built-in message', function (): void {
        $header = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFAAH/q842iQAAAABJRU5ErkJggg==');

        expect(failuresFor(sizedUpload('photo.png', $header, 2048)))->toBeEmpty();

        $failures = failuresFor(sizedUpload('photo.png', $header, 2049));

        expect($failures)->toHaveCount(1)
            ->and($failures[0])->toBe(__('validation.max.file', [
                'attribute' => 'file',
                'max' => 2048,
            ]));
    });
});
