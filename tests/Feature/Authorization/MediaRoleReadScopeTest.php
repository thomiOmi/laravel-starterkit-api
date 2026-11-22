<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\IAM\Database\Seeders\IAMSeeder;
use Modules\IAM\Models\User;
use Modules\Media\Database\Factories\MediaFactory;
use Modules\Media\Http\Controllers\V1\MediaDeleteController;
use Modules\Media\Http\Controllers\V1\MediaListController;
use Modules\Media\Http\Controllers\V1\MediaModifierController;
use Modules\Media\Http\Controllers\V1\MediaShowController;
use Modules\Media\Models\Media;

/*
 * These regression tests live at the application level because the Media
 * module may not reference the IAM seeder across module boundaries. They
 * prove the seeded "user" role no longer grants cross-owner read access.
 */
covers([
    MediaShowController::class,
    MediaModifierController::class,
    MediaListController::class,
    MediaDeleteController::class,
]);

/**
 * Persist a media row with real decodable bytes so the modifier route can
 * be exercised instead of failing on a missing file.
 */
function seedOwnedImageMedia(User $owner, bool $public = false): Media
{
    $factory = MediaFactory::new()->forModel($owner)->uploadedBy($owner);

    if ($public) {
        $factory = $factory->public();
    }

    /** @var Media $media */
    $media = $factory->createOne(['mime_type' => 'image/jpeg']);

    $path = $media->getPath() ?? throw new RuntimeException('The seeded media has no storage path.');

    $file = UploadedFile::fake()->image('owned.jpg', 200, 100);
    Storage::disk($media->disk)->put($path, (string) $file->getContent());

    return $media;
}

describe('seeded user role media read scope', function (): void {
    beforeEach(function (): void {
        Storage::fake('public');
        Storage::fake('local');
    });

    it('rejects showing another users private media', function (): void {
        $this->seed(IAMSeeder::class);
        $owner = loginAsUserRole();
        $media = seedOwnedImageMedia($owner);

        loginAsUserRole();

        assertProblemResponse($this->getJson("/api/v1/media/{$media->id}"), 403);
    });

    it('does not mint a signed url for another users private media', function (): void {
        $this->seed(IAMSeeder::class);
        $owner = loginAsUserRole();
        $media = seedOwnedImageMedia($owner);

        loginAsUserRole();

        $response = $this->getJson("/api/v1/media/{$media->id}?expires=30");

        assertProblemResponse($response, 403);
        expect($response->json('data.url'))->toBeNull();
    });

    it('rejects on-demand conversions of another users private media', function (): void {
        $this->seed(IAMSeeder::class);
        $owner = loginAsUserRole();
        $media = seedOwnedImageMedia($owner);

        loginAsUserRole();

        assertProblemResponse($this->getJson("/api/v1/media/{$media->id}/s/w_100,f_webp"), 403);
    });

    it('keeps another users media out of the list', function (): void {
        $this->seed(IAMSeeder::class);
        $owner = loginAsUserRole();
        $foreign = seedOwnedImageMedia($owner);

        $viewer = loginAsUserRole();
        $own = MediaFactory::new()->forModel($viewer)->createOne();

        $response = $this->getJson('/api/v1/media');

        assertSuccessResponse($response, 200);
        assertPaginatedResponse($response);

        $ids = $response->json('data.*.id');

        expect($ids)->toContain($own->id)
            ->and($ids)->not->toContain($foreign->id);
    });

    it('keeps owner access to their own private media', function (): void {
        $this->seed(IAMSeeder::class);
        $owner = loginAsUserRole();
        $media = seedOwnedImageMedia($owner);

        assertSuccessResponse($this->getJson("/api/v1/media/{$media->id}"), 200);
    });

    it('lets the owner delete their own private media', function (): void {
        $this->seed(IAMSeeder::class);
        $owner = loginAsUserRole();
        $media = seedOwnedImageMedia($owner);

        $delete = $this->deleteJson("/api/v1/media/{$media->id}");

        assertSuccessResponse($delete, 200);
        expect(Media::query()->whereKey($media->id)->exists())->toBeFalse();
    });

    it('keeps public media readable by other users', function (): void {
        $this->seed(IAMSeeder::class);
        $owner = loginAsUserRole();
        $media = seedOwnedImageMedia($owner, public: true);

        loginAsUserRole();

        assertSuccessResponse($this->getJson("/api/v1/media/{$media->id}"), 200);
    });

    it('keeps admin access to another users private media', function (): void {
        $this->seed(IAMSeeder::class);
        $owner = loginAsUserRole();
        $media = seedOwnedImageMedia($owner);

        loginAsAdmin();

        assertSuccessResponse($this->getJson("/api/v1/media/{$media->id}"), 200);
    });
});
