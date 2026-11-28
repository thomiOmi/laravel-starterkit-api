<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Modules\Media\Rules\AllowedFileName;

covers(AllowedFileName::class);

describe('AllowedFileName rule', function (): void {
    it('fails files with a disallowed segment and passes clean files', function (): void {
        $rejected = Validator::make(
            ['file' => UploadedFile::fake()->image('shell.php.jpg', 20, 20)],
            ['file' => [new AllowedFileName]]
        );

        expect($rejected->fails())->toBeTrue();

        $accepted = Validator::make(
            ['file' => UploadedFile::fake()->image('photo.jpg', 20, 20)],
            ['file' => [new AllowedFileName]]
        );

        expect($accepted->fails())->toBeFalse();
    });

    it('rejects the dll extension and ASCII control characters with the shared message', function (string $name): void {
        $validator = Validator::make(
            ['file' => UploadedFile::fake()->createWithContent($name, 'body')],
            ['file' => [new AllowedFileName]],
        );

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->first('file'))->toBe(__('validation.media_disallowed_extension'));
    })->with([
        'dll extension' => ['payload.dll'],
        'null byte' => ["a\x00b.txt"],
        'control character' => ["a\x01b.txt"],
        'delete character' => ["a\x7Fb.txt"],
    ]);

    it('accepts unicode, emoji and long names without control characters', function (string $name): void {
        $validator = Validator::make(
            ['file' => UploadedFile::fake()->createWithContent($name, 'body')],
            ['file' => [new AllowedFileName]],
        );

        expect($validator->fails())->toBeFalse();
    })->with([
        'unicode and emoji' => ['nama-日本語-😀.txt'],
        'long name' => [str_repeat('a', 200).'.txt'],
    ]);

    it('ignores non-file values', function (): void {
        $validator = Validator::make(
            ['file' => 'not-a-file'],
            ['file' => [new AllowedFileName]]
        );

        expect($validator->fails())->toBeFalse();
    });
});
