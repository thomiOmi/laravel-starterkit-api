<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use Modules\IAM\Models\Permission;
use Modules\Media\Database\Factories\MediaFactory;
use Modules\Media\Http\Controllers\V1\MediaShowController;

covers(MediaShowController::class);

describe('GET /api/v1/media/{media}', function () {
    it('allows the owner to view without any permission', function () {
        $user = loginAsUser();
        $media = MediaFactory::new()->forUser($user)->createOne();

        $response = $this->getJson("/api/v1/media/{$media->id}");

        assertSuccessResponse($response, 200);
        expect($response->json('data.id'))->toBe($media->id);
    });

    it('allows a viewer with the view permission', function () {
        Permission::firstOrCreate(['name' => PermissionEnum::MediaView->value, 'guard_name' => 'sanctum']);
        $viewer = loginAsUser();
        $viewer->givePermissionTo(PermissionEnum::MediaView->value);
        $media = MediaFactory::new()->createOne();

        assertSuccessResponse($this->getJson("/api/v1/media/{$media->id}"), 200);
    });

    it('rejects strangers without permission', function () {
        loginAsUser();
        $media = MediaFactory::new()->createOne();

        assertProblemResponse($this->getJson("/api/v1/media/{$media->id}"), 403);
    });

    it('rejects unauthenticated requests', function () {
        $media = MediaFactory::new()->createOne();

        $this->getJson("/api/v1/media/{$media->id}")->assertUnauthorized();
    });

    it('returns 404 for unknown media', function () {
        loginAsUser();

        $this->getJson('/api/v1/media/01AAAAAAAAAAAAAAAAAAAAAAAA')->assertNotFound();
    });

    it('swaps the url for a signed link when expires is passed', function () {
        $user = loginAsUser();
        $media = MediaFactory::new()->forUser($user)->createOne();

        $response = $this->getJson("/api/v1/media/{$media->id}?expires=30");

        assertSuccessResponse($response, 200);
        expect($response->json('data.url'))->toContain('/api/v1/media/'.$media->id.'/file')
            ->and($response->json('data.url'))->toContain('signature=');
    });

    it('keeps the private url null without an expires parameter', function () {
        $user = loginAsUser();
        $media = MediaFactory::new()->forUser($user)->createOne();

        $response = $this->getJson("/api/v1/media/{$media->id}");

        assertSuccessResponse($response, 200);
        expect($response->json('data.url'))->toBeNull();
    });

    it('rejects out-of-bounds expiry values', function (int $expires) {
        $user = loginAsUser();
        $media = MediaFactory::new()->forUser($user)->createOne();

        $response = $this->getJson("/api/v1/media/{$media->id}?expires={$expires}");

        assertProblemResponse($response, 422, 'validation');
        $response->assertJsonValidationErrors(['expires']);
    })->with([
        'below minimum' => 0,
        'above maximum' => 1441,
    ]);
});
