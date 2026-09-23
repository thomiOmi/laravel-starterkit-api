<?php

declare(strict_types=1);

use Modules\Media\Support\MediaConversion;

covers(MediaConversion::class);

describe('MediaConversion builder', function (): void {
    it('accepts valid width height fit format and quality', function (): void {
        $conversion = new MediaConversion('thumb')
            ->width(320)
            ->height(200)
            ->fit('cover')
            ->format('jpg')
            ->quality(85);

        expect($conversion->width)->toBe(320)
            ->and($conversion->height)->toBe(200)
            ->and($conversion->fit)->toBe('cover')
            ->and($conversion->format)->toBe('jpg')
            ->and($conversion->quality)->toBe(85);
    });

    it('rejects out of bounds width', function (): void {
        expect(fn (): MediaConversion => new MediaConversion('thumb')->width(31))
            ->toThrow(InvalidArgumentException::class, 'Width must be between 32 and 2000.')
            ->and(fn (): MediaConversion => new MediaConversion('thumb')->width(2001))->toThrow(InvalidArgumentException::class, 'Width must be between 32 and 2000.');
    });

    it('rejects out of bounds height', function (): void {
        expect(fn (): MediaConversion => new MediaConversion('thumb')->height(0))
            ->toThrow(InvalidArgumentException::class, 'Height must be between 32 and 2000.')
            ->and(fn (): MediaConversion => new MediaConversion('thumb')->height(2001))->toThrow(InvalidArgumentException::class, 'Height must be between 32 and 2000.');
    });

    it('rejects unknown fit values', function (): void {
        expect(fn (): MediaConversion => new MediaConversion('thumb')->fit('stretch'))
            ->toThrow(InvalidArgumentException::class, 'Fit must be one of: contain, cover, fill.');
    });

    it('rejects unsupported formats', function (): void {
        expect(fn (): MediaConversion => new MediaConversion('thumb')->format('gif'))
            ->toThrow(InvalidArgumentException::class, 'Format must be one of: webp, jpg, jpeg.');
    });

    it('normalizes format to lowercase', function (): void {
        $conversion = new MediaConversion('thumb')->format('JPG');

        expect($conversion->format)->toBe('jpg');
    });

    it('rejects out of bounds quality', function (): void {
        expect(fn (): MediaConversion => new MediaConversion('thumb')->quality(0))
            ->toThrow(InvalidArgumentException::class, 'Quality must be between 1 and 100.')
            ->and(fn (): MediaConversion => new MediaConversion('thumb')->quality(101))->toThrow(InvalidArgumentException::class, 'Quality must be between 1 and 100.');
    });
});

describe('MediaConversion modifiers', function (): void {
    it('parses ipx style modifiers with fit', function (): void {
        $parsed = MediaConversion::parse('w_320,h_200,f_webp,q_80,fit_cover');

        expect($parsed)->toMatchArray([
            'w' => 320,
            'h' => 200,
            'f' => 'webp',
            'q' => 80,
            'fit' => 'cover',
        ]);
    });

    it('parses slash style modifiers with fit', function (): void {
        $parsed = MediaConversion::parse('320x200/fit/cover/f/jpg');

        expect($parsed)->toMatchArray([
            'w' => 320,
            'h' => 200,
            'fit' => 'cover',
            'f' => 'jpg',
        ]);
    });

    it('rejects invalid fit in modifiers', function (): void {
        expect(fn (): array => MediaConversion::parse('320/fit/stretch'))
            ->toThrow(InvalidArgumentException::class, 'Fit must be one of: contain, cover, fill.');
    });

    it('rejects invalid format in modifiers', function (): void {
        expect(fn (): array => MediaConversion::parse('320/f/gif'))
            ->toThrow(InvalidArgumentException::class, 'Format must be one of: webp, jpg, jpeg.');
    });

    it('rejects empty modifiers', function (): void {
        expect(fn (): array => MediaConversion::parse(''))
            ->toThrow(InvalidArgumentException::class, 'Modifiers cannot be empty.');
    });

    it('builds a conversion from modifiers including fit', function (): void {
        $conversion = MediaConversion::fromModifiers('w_320,f_webp,q_70,fit_cover');

        expect($conversion->width)->toBe(320)
            ->and($conversion->format)->toBe('webp')
            ->and($conversion->quality)->toBe(70)
            ->and($conversion->fit)->toBe('cover');
    });

    it('changes derived path when fit differs', function (): void {
        $modifiers = ['w' => 320, 'fit' => 'cover'];
        $withCover = MediaConversion::derivedPath('m1', $modifiers, 'abc123456789', 'webp');

        $modifiers['fit'] = 'contain';
        $withContain = MediaConversion::derivedPath('m1', $modifiers, 'abc123456789', 'webp');

        expect($withCover)->toContain('fit_cover')
            ->and($withContain)->toContain('fit_contain')
            ->and($withCover)->not->toBe($withContain);
    });
});
