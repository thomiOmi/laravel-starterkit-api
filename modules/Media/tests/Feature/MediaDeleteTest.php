<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use Illuminate\Support\Facades\Storage;
use Modules\IAM\Models\Permission;
use Modules\Media\Database\Factories\MediaFactory;
use Modules\Media\Http\Controllers\V1\MediaDeleteController;
use Modules\Media\Models\Media;

covers(MediaDeleteController::class);

describe('DELETE /api/v1/media/{media}', function () {
    beforeEach(function () {
        Storage::fake('public');
    });

    it('allows the owner to delete without any permission and removes the file', function () {
        $user = loginAsUser();
        $media = MediaFactory::new()->forUser($user)->createOne();
        Storage::disk($media->disk)->put($media->path, 'content');

        $response = $this->deleteJson("/api/v1/media/{$media->id}");

        assertSuccessResponse($response, 200);
        expect(Media::query()->whereKey($media->id)->exists())->toBeFalse();

        Storage::disk($media->disk)->assertMissing($media->path);
    });

    it('allows a user with the delete permission to remove other media', function () {
        Permission::firstOrCreate(['name' => PermissionEnum::MediaDelete->value, 'guard_name' => 'sanctum']);
        $staff = loginAsUser();
        $staff->givePermissionTo(PermissionEnum::MediaDelete->value);
        $media = MediaFactory::new()->createOne();
        Storage::disk($media->disk)->put($media->path, 'content');

        assertSuccessResponse($this->deleteJson("/api/v1/media/{$media->id}"), 200);

        Storage::disk($media->disk)->assertMissing($media->path);
    });

    it('rejects strangers without permission', function () {
        loginAsUser();
        $media = MediaFactory::new()->createOne();

        assertProblemResponse($this->deleteJson("/api/v1/media/{$media->id}"), 403);
        expect(Media::query()->whereKey($media->id)->exists())->toBeTrue();
    });

    it('rejects unauthenticated requests', function () {
        $media = MediaFactory::new()->createOne();

        $this->deleteJson("/api/v1/media/{$media->id}")->assertUnauthorized();
    });
});
