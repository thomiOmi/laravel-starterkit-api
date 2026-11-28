<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Http\Controllers\V1\MediaUploadController;

covers(MediaUploadController::class);

describe('file name guard over HTTP', function (): void {
    beforeEach(function (): void {
        Storage::fake('public');
        Storage::fake('local');
        ensurePermissionExists(PermissionEnum::MediaCreate->value);
        $user = loginAsUser();
        $user->givePermissionTo(PermissionEnum::MediaCreate->value);
    });

    it('rejects dll and ASCII control characters in client names', function (string $name): void {
        $response = $this->post('/api/v1/media', [
            'file' => UploadedFile::fake()->createWithContent($name, 'hello text'),
            'collection_name' => 'default',
        ]);

        assertProblemResponse($response, 422);
        expect($response->json('errors.file.0'))->toBe(__('validation.media_disallowed_extension'));
    })->with([
        'dll extension' => ['payload.dll'],
        'control character' => ["a\x01b.txt"],
        'delete character' => ["a\x7Fb.txt"],
    ]);

    it('accepts unicode, emoji and long valid names', function (string $name): void {
        $response = $this->post('/api/v1/media', [
            'file' => UploadedFile::fake()->createWithContent($name, 'hello text'),
            'collection_name' => 'default',
        ]);

        assertSuccessResponse($response, 201);
        expect($response->json('data.media.original_name'))->toBe($name);
    })->with([
        'unicode and emoji' => ['nama-日本語-😀.txt'],
        'long name' => [str_repeat('a', 200).'.txt'],
    ]);
});
