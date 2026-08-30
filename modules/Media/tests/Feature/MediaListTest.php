<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Media\Database\Factories\MediaFactory;
use Modules\Media\Http\Controllers\V1\MediaListController;

covers(MediaListController::class);

describe('GET /api/v1/media', function () {
    beforeEach(function () {
        DB::table('permissions')->insertOrIgnore([
            'id' => (string) Str::ulid(),
            'name' => PermissionEnum::MediaView->value,
            'guard_name' => 'sanctum',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    });

    it('lists only the authenticated user media', function () {
        $user = loginAsUser();
        $user->givePermissionTo(PermissionEnum::MediaView->value);

        MediaFactory::new()->forModel($user)->count(2)->create();
        MediaFactory::new()->count(3)->create();

        $response = $this->getJson('/api/v1/media');

        assertSuccessResponse($response, 200);
        assertPaginatedResponse($response);
        expect($response->json('data'))->toHaveCount(2);
    });

    it('filters by collection name', function () {
        $user = loginAsUser();
        $user->givePermissionTo(PermissionEnum::MediaView->value);

        MediaFactory::new()->forModel($user)->inCollection('avatars')->create();
        MediaFactory::new()->forModel($user)->inCollection('documents')->create();

        $response = $this->getJson('/api/v1/media?filter[collection_name]=avatars');

        assertSuccessResponse($response, 200);
        expect($response->json('data'))->toHaveCount(1)
            ->and($response->json('data.0.collection_name'))->toBe('avatars');
    });

    it('rejects unauthenticated requests', function () {
        $this->getJson('/api/v1/media')->assertUnauthorized();
    });

    it('rejects users without the view permission', function () {
        loginAsUser();

        assertProblemResponse($this->getJson('/api/v1/media'), 403);
    });
});

it('returns null original_name when meta is absent', function () {
    DB::table('permissions')->insertOrIgnore([
        'id' => (string) Str::ulid(),
        'name' => PermissionEnum::MediaView->value,
        'guard_name' => 'sanctum',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $viewer = loginAsUser();
    $viewer->givePermissionTo(PermissionEnum::MediaView->value);
    $media = MediaFactory::new()->forModel($viewer)->createOne(['meta' => null, 'original_name' => null]);

    $response = $this->getJson('/api/v1/media');

    assertSuccessResponse($response, 200);
    expect($response->json('data.0.original_name'))->toBeNull();
});
