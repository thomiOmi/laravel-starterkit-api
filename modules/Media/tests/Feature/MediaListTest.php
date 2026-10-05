<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Media\Database\Factories\MediaFactory;
use Modules\Media\Http\Controllers\V1\MediaListController;

covers(MediaListController::class);

describe('GET /api/v1/media', function (): void {
    beforeEach(function (): void {
        DB::table('permissions')->insertOrIgnore([
            'id' => (string) Str::ulid(),
            'name' => PermissionEnum::MediaView->value,
            'guard_name' => 'sanctum',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    });

    it('lists only the authenticated user media', function (): void {
        $user = loginAsUser();
        $user->givePermissionTo(PermissionEnum::MediaView->value);

        MediaFactory::new()->forModel($user)->count(2)->create();
        MediaFactory::new()->count(3)->create();

        $response = $this->getJson('/api/v1/media');

        assertSuccessResponse($response, 200);
        assertPaginatedResponse($response);
        expect($response->json('data'))->toHaveCount(2);
    });

    it('honours the requested page size and reports the remaining pages', function (): void {
        $user = loginAsUser();

        MediaFactory::new()->forModel($user)->count(3)->create();

        $firstPage = $this->getJson('/api/v1/media?page[size]=2');

        assertSuccessResponse($firstPage, 200);
        assertPaginatedResponse($firstPage);
        expect($firstPage->json('data'))->toHaveCount(2)
            ->and($firstPage->json('meta.per_page'))->toBe(2)
            ->and($firstPage->json('meta.has_more'))->toBeTrue();

        $secondPage = $this->getJson('/api/v1/media?page[size]=2&page[number]=2');

        assertSuccessResponse($secondPage, 200);
        assertPaginatedResponse($secondPage);
        expect($secondPage->json('data'))->toHaveCount(1)
            ->and($secondPage->json('meta.has_more'))->toBeFalse();
    });

    it('does not repeat rows across pages', function (): void {
        $user = loginAsUser();

        MediaFactory::new()->forModel($user)->count(3)->create();

        $firstData = $this->getJson('/api/v1/media?page[size]=2')->json('data');
        $secondData = $this->getJson('/api/v1/media?page[size]=2&page[number]=2')->json('data');

        throw_unless(is_array($firstData), RuntimeException::class, 'First page data was not an array.');
        throw_unless(is_array($secondData), RuntimeException::class, 'Second page data was not an array.');

        $firstIds = collect($firstData)->pluck('id');
        $secondIds = collect($secondData)->pluck('id');

        expect($firstIds->count())->toBe(2)
            ->and($secondIds->count())->toBe(1)
            ->and($firstIds->intersect($secondIds))->toBeEmpty();
    });

    it('filters by collection name', function (): void {
        $user = loginAsUser();
        $user->givePermissionTo(PermissionEnum::MediaView->value);

        MediaFactory::new()->forModel($user)->inCollection('avatars')->create();
        MediaFactory::new()->forModel($user)->inCollection('documents')->create();

        $response = $this->getJson('/api/v1/media?filter[collection_name]=avatars');

        assertSuccessResponse($response, 200);
        expect($response->json('data'))->toHaveCount(1)
            ->and($response->json('data.0.collection_name'))->toBe('avatars');
    });

    it('sorts newest first when no sort parameter is given', function (): void {
        $user = loginAsUser();

        $oldest = MediaFactory::new()->forModel($user)->createOne(['created_at' => now()->subDays(3)]);
        $middle = MediaFactory::new()->forModel($user)->createOne(['created_at' => now()->subDays(2)]);
        $newest = MediaFactory::new()->forModel($user)->createOne(['created_at' => now()->subDay()]);

        $response = $this->getJson('/api/v1/media');

        assertSuccessResponse($response, 200);

        $data = $response->json('data');
        throw_unless(is_array($data), RuntimeException::class, 'Response data was not an array.');

        expect(collect($data)->pluck('id')->all())->toBe([$newest->id, $middle->id, $oldest->id]);
    });

    it('sorts by the requested whitelisted column', function (string $sort, string $column, array $expected): void {
        $user = loginAsUser();

        MediaFactory::new()->forModel($user)->createOne(['size' => 300, 'order_column' => 3]);
        MediaFactory::new()->forModel($user)->createOne(['size' => 100, 'order_column' => 1]);
        MediaFactory::new()->forModel($user)->createOne(['size' => 200, 'order_column' => 2]);

        $response = $this->getJson('/api/v1/media?sort='.$sort);

        assertSuccessResponse($response, 200);

        $data = $response->json('data');
        throw_unless(is_array($data), RuntimeException::class, 'Response data was not an array.');

        expect(collect($data)->pluck($column)->all())->toBe($expected);
    })->with([
        'ascending size' => ['size', 'size', [100, 200, 300]],
        'descending size' => ['-size', 'size', [300, 200, 100]],
        'ascending order_column' => ['order_column', 'order_column', [1, 2, 3]],
        'descending order_column' => ['-order_column', 'order_column', [3, 2, 1]],
    ]);

    it('rejects a sort column outside the whitelist', function (): void {
        $user = loginAsUser();

        MediaFactory::new()->forModel($user)->count(2)->create();

        $response = $this->getJson('/api/v1/media?sort=mime_type');

        assertProblemResponse($response, 400);
    });

    it('rejects unauthenticated requests', function (): void {
        $this->getJson('/api/v1/media')->assertUnauthorized();
    });

    it('allows owners to list their own media without the view permission', function (): void {
        $user = loginAsUser();

        MediaFactory::new()->forModel($user)->createOne();

        $response = $this->getJson('/api/v1/media');

        assertSuccessResponse($response, 200);
        expect($response->json('data'))->toHaveCount(1);
    });
});

it('returns null original_name when the column is absent', function (): void {
    DB::table('permissions')->insertOrIgnore([
        'id' => (string) Str::ulid(),
        'name' => PermissionEnum::MediaView->value,
        'guard_name' => 'sanctum',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $viewer = loginAsUser();
    $viewer->givePermissionTo(PermissionEnum::MediaView->value);

    $media = MediaFactory::new()->forModel($viewer)->createOne(['original_name' => null]);

    $response = $this->getJson('/api/v1/media');

    assertSuccessResponse($response, 200);
    expect($response->json('data.0.original_name'))->toBeNull();
});
