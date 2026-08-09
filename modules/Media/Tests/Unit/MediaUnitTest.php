<?php

declare(strict_types=1);

use App\Enums\MediaVisibilityEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Models\User;
use Modules\Media\Actions\DeleteMediaAction;
use Modules\Media\Actions\UploadMediaAction;
use Modules\Media\Database\Factories\MediaFactory;
use Modules\Media\Models\Media;
use Modules\Media\Payloads\V1\MediaUploadPayload;
use Modules\Media\Requests\V1\MediaUploadRequest;

uses(RefreshDatabase::class);

describe('UploadMediaAction', function (): void {
    it('stores a public file on the public disk', function (): void {
        Storage::fake('public');
        $uploader = UserFactory::new()->createOne();
        $payload = new MediaUploadPayload(
            file: UploadedFile::fake()->create('photo.png', 100),
            visibility: MediaVisibilityEnum::Public,
            uploadedBy: $uploader->id,
        );

        $media = app(UploadMediaAction::class)->handle($payload);

        expect($media->disk)->toBe('public')
            ->and($media->mime_type)->toBe('image/png')
            ->and($media->size)->toBeGreaterThan(0)
            ->and($media->uploaded_by)->toBe($uploader->id)
            ->and($media->meta)->toBeArray()
            ->and($media->meta['original_name'] ?? null)->toBe('photo.png');

        Storage::disk('public')->assertExists($media->path);
    })->group('module:media');

    it('stores a private file on the local disk', function (): void {
        Storage::fake('local');
        $uploader = UserFactory::new()->createOne();
        $payload = new MediaUploadPayload(
            file: UploadedFile::fake()->create('secret.txt', 100),
            visibility: MediaVisibilityEnum::Private,
            uploadedBy: $uploader->id,
        );

        $media = app(UploadMediaAction::class)->handle($payload);

        expect($media->disk)->toBe('local')
            ->and($media->meta['extension'] ?? null)->toBe('txt');

        Storage::disk('local')->assertExists($media->path);
    })->group('module:media');
});

describe('DeleteMediaAction', function (): void {
    it('deletes the physical file and the record', function (): void {
        Storage::fake('public');
        $media = MediaFactory::new()->createOne(['disk' => 'public']);
        Storage::disk('public')->put($media->path, 'content');

        $result = app(DeleteMediaAction::class)->handle($media);

        expect($result)->toBeTrue();
        Storage::disk('public')->assertMissing($media->path);
        expect(Media::find($media->id))->toBeNull();
    })->group('module:media');

    it('deletes the record even when the physical file is missing', function (): void {
        Storage::fake('local');
        $media = MediaFactory::new()->createOne(['disk' => 'local']);

        $result = app(DeleteMediaAction::class)->handle($media);

        expect($result)->toBeTrue();
        expect(Media::find($media->id))->toBeNull();
    })->group('module:media');
});

describe('MediaUploadPayload', function (): void {
    /**
     * @param  array<string, string>  $parameters
     */
    function mediaUploadRequest(array $parameters = []): MediaUploadRequest
    {
        $request = MediaUploadRequest::create('/api/v1/media', 'POST', $parameters);
        $request->files->set('file', UploadedFile::fake()->create('doc.pdf', 100));

        return $request;
    }

    it('defaults to public visibility and uses the authenticated user', function (): void {
        $user = UserFactory::new()->createOne();
        $request = mediaUploadRequest();
        $request->setUserResolver(fn (): User => $user);

        $payload = MediaUploadPayload::fromRequest($request);

        expect($payload->visibility)->toBe(MediaVisibilityEnum::Public)
            ->and($payload->uploadedBy)->toBe($user->id);
    })->group('module:media');

    it('uses the requested visibility', function (): void {
        $user = UserFactory::new()->createOne();
        $request = mediaUploadRequest(['visibility' => 'private']);
        $request->setUserResolver(fn (): User => $user);

        $payload = MediaUploadPayload::fromRequest($request);

        expect($payload->visibility)->toBe(MediaVisibilityEnum::Private);
    })->group('module:media');

    it('throws when no file is uploaded', function (): void {
        $user = UserFactory::new()->createOne();
        $request = mediaUploadRequest();
        $request->files->remove('file');
        $request->setUserResolver(fn (): User => $user);

        expect(fn () => MediaUploadPayload::fromRequest($request))
            ->toThrow(InvalidArgumentException::class, 'Uploaded file is required.');
    })->group('module:media');

    it('throws when there is no authenticated user', function (): void {
        $request = mediaUploadRequest();

        expect(fn () => MediaUploadPayload::fromRequest($request))
            ->toThrow(InvalidArgumentException::class, 'Authenticated user is required.');
    })->group('module:media');

    it('converts to an array for Eloquent', function (): void {
        $payload = new MediaUploadPayload(
            file: UploadedFile::fake()->create('doc.pdf', 100),
            visibility: MediaVisibilityEnum::Private,
            uploadedBy: 'user-01',
        );

        expect($payload->toArray())->toBe([
            'visibility' => 'private',
            'uploaded_by' => 'user-01',
        ]);
    })->group('module:media');
});
