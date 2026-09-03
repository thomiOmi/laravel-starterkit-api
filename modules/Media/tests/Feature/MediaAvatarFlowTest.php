<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Media\Http\Controllers\V1\MediaUploadController;

covers(MediaUploadController::class);

describe('avatar end-to-end flow', function () {
    it('uploads media then attaches it through the profile update endpoint', function () {
        Storage::fake('public');
        DB::table('permissions')->insertOrIgnore([
            'id' => (string) Str::ulid(),
            'name' => PermissionEnum::MediaCreate->value,
            'guard_name' => 'sanctum',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = loginAsUser();
        $user->givePermissionTo(PermissionEnum::MediaCreate->value);

        $upload = $this->post('/api/v1/media', [
            'file' => UploadedFile::fake()->image('me.png', 32, 32),
            'collection_name' => 'avatars',
        ]);

        assertSuccessResponse($upload, 201);

        $mediaId = $upload->json('data.media.id');
        $expectedUrl = $upload->json('data.url');

        $update = $this->putJson('/api/v1/auth/me', ['avatar' => $mediaId]);

        assertSuccessResponse($update, 200);
        expect($update->json('data.user.avatar'))->toBe($expectedUrl)
            ->and($user->fresh()?->avatar)->toBe($expectedUrl);
    });
});
