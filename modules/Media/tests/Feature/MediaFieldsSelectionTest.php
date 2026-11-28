<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;
use Modules\Media\Database\Factories\MediaFactory;
use Modules\Media\Http\Controllers\V1\MediaListController;

covers(MediaListController::class);

describe('GET /api/v1/media with sparse fields', function (): void {
    beforeEach(function (): void {
        Storage::fake('public');
        Storage::fake('local');
    });

    it('returns 200 for requested fields alone and combined with filter or sort', function (string $query): void {
        $user = loginAsUser();
        MediaFactory::new()->forModel($user)->createOne();

        $response = $this->getJson('/api/v1/media'.$query);

        assertSuccessResponse($response, 200);
        expect($response->json('data'))->toHaveCount(1);
    })->with([
        'id and name' => ['?fields[media]=id,name'],
        'id, mime_type and size' => ['?fields[media]=id,mime_type,size'],
        'no fields' => [''],
        'filter plus fields' => ['?filter[collection_name]=default&fields[media]=id,name'],
        'sort plus fields' => ['?sort=-size&fields[media]=id,name'],
    ]);

    it('keeps conversions and the owner model usable when fields are sparse', function (): void {
        $user = loginAsUser();
        $media = MediaFactory::new()->forModel($user)->public()->createOne();
        $media->conversions()->create([
            'name' => 'medium',
            'disk' => 'public',
            'path' => 'conversions/medium.png',
            'mime_type' => 'image/png',
            'size' => 10,
            'etag' => 'etag',
        ]);
        Storage::disk('public')->put('conversions/medium.png', 'content');

        $response = $this->getJson('/api/v1/media?fields[media]=id,name');

        assertSuccessResponse($response, 200);
        expect($response->json('data.0.conversions'))->toHaveKey('medium')
            ->and($response->json('data.0.model_type'))->toBe($media->model_type)
            ->and($response->json('data.0.model_id'))->toBe($media->model_id);
    });
});
