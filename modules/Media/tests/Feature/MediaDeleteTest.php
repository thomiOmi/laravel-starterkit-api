<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Media\Database\Factories\MediaFactory;
use Modules\Media\Events\MediaDeleted;
use Modules\Media\Http\Controllers\V1\MediaDeleteController;
use Modules\Media\Models\Media;

covers(MediaDeleteController::class);

describe('DELETE /api/v1/media/{media}', function () {
    beforeEach(function () {
        Storage::fake('public');
    });

    it('allows the owner to delete without any permission and removes the file', function () {
        Event::fake([MediaDeleted::class]);
        $user = loginAsUser();
        $media = MediaFactory::new()->forModel($user)->createOne();
        Storage::disk($media->disk)->put($media->path, 'content');
        Storage::disk($media->disk)->put('variants/'.$media->id.'/1-32-webp.webp', 'thumb');

        $response = $this->deleteJson("/api/v1/media/{$media->id}");

        assertSuccessResponse($response, 200);
        expect(Media::query()->whereKey($media->id)->exists())->toBeFalse();

        Storage::disk($media->disk)->assertMissing($media->path);
        Storage::disk($media->disk)->assertMissing('variants/'.$media->id.'/1-32-webp.webp');
        // Identity closure would restore the soft-deleted model through
        // SerializesModels, so only assert the event type here.
        Event::assertDispatched(MediaDeleted::class);
    });

    it('allows a user with the delete permission to remove other media', function () {
        DB::table('permissions')->insertOrIgnore([
            'id' => (string) Str::ulid(),
            'name' => PermissionEnum::MediaDelete->value,
            'guard_name' => 'sanctum',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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
