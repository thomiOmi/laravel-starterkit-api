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

    it('ignores non-file values', function (): void {
        $validator = Validator::make(
            ['file' => 'not-a-file'],
            ['file' => [new AllowedFileName]]
        );

        expect($validator->fails())->toBeFalse();
    });
});
