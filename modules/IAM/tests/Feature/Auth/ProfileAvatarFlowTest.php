<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\IAM\Http\Controllers\V1\UpdateProfileController;
use Modules\Media\Models\Media;

covers(UpdateProfileController::class);

describe('avatar end-to-end flow', function () {
    it('uploads an avatar file directly through the profile update endpoint', function () {
        Storage::fake('public');

        $user = loginAsUser();

        $update = $this->put('/api/v1/auth/me', [
            'avatar' => UploadedFile::fake()->image('me.png', 32, 32),
        ]);

        assertSuccessResponse($update, 200);

        $avatarUrl = $update->json('data.user.avatar');

        expect($avatarUrl)->toContain('avatars/')
            ->and($user->fresh()?->avatar)->toBe($avatarUrl)
            ->and(Media::query()->where('model_id', $user->id)->where('collection_name', 'avatars')->count())->toBe(1);
    });

    it('resolves the avatar url with the configured prefix', function () {
        config(['media.prefix' => 'tenant-a']);
        Storage::fake('public');

        $user = loginAsUser();

        $update = $this->put('/api/v1/auth/me', [
            'avatar' => UploadedFile::fake()->image('me.png', 32, 32),
        ]);

        assertSuccessResponse($update, 200);
        expect($update->json('data.user.avatar'))->toContain('tenant-a/avatars/');
    });

    it('replaces the previous avatar file on re-upload', function () {
        Storage::fake('public');

        $user = loginAsUser();

        $first = $this->put('/api/v1/auth/me', [
            'avatar' => UploadedFile::fake()->image('first.png', 32, 32),
        ]);

        assertSuccessResponse($first, 200);

        $firstUrl = $first->json('data.user.avatar');
        $firstPath = is_string($firstUrl) ? parse_url($firstUrl, PHP_URL_PATH) : null;

        expect($firstUrl)->toBeString();

        if (! is_string($firstPath)) {
            $this->fail('Avatar URL has no path part.');
        }

        $second = $this->put('/api/v1/auth/me', [
            'avatar' => UploadedFile::fake()->image('second.png', 32, 32),
        ]);

        assertSuccessResponse($second, 200);

        expect($second->json('data.user.avatar'))->not->toBe($firstUrl)
            ->and(Media::query()->where('model_id', $user->id)->where('collection_name', 'avatars')->count())->toBe(1);

        Storage::disk('public')->assertMissing(Str::after($firstPath, '/storage/'));
    });

    it('rejects non-image avatars with a validation error', function () {
        Storage::fake('public');
        loginAsUser();

        $response = $this->put('/api/v1/auth/me', [
            'avatar' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
        ]);

        assertProblemResponse($response, 422, 'validation');
        $response->assertJsonValidationErrors(['avatar']);
    });

    it('rejects double extensions with a validation error', function () {
        Storage::fake('public');
        loginAsUser();

        $response = $this->put('/api/v1/auth/me', [
            'avatar' => UploadedFile::fake()->image('shell.php.png', 32, 32),
        ]);

        assertProblemResponse($response, 422, 'validation');
        $response->assertJsonValidationErrors(['avatar']);
    });

    it('rejects the legacy media id payload', function () {
        Storage::fake('public');
        loginAsUser();

        $response = $this->putJson('/api/v1/auth/me', ['avatar' => (string) Str::ulid()]);

        assertProblemResponse($response, 422, 'validation');
        $response->assertJsonValidationErrors(['avatar']);
    });
});
