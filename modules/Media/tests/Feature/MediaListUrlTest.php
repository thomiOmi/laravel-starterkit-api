<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;
use Modules\Media\Database\Factories\MediaFactory;
use Modules\Media\Http\Controllers\V1\MediaListController;
use Modules\Media\Http\Controllers\V1\MediaShowController;

covers(MediaListController::class, MediaShowController::class);

describe('urls in media lists', function (): void {
    beforeEach(function (): void {
        Storage::fake('public');
        Storage::fake('local');
    });

    it('gives private media no url and public media its stored url', function (): void {
        $user = loginAsUser();
        $private = MediaFactory::new()->forModel($user)->createOne(['file_name' => 'private.png']);
        $public = MediaFactory::new()->forModel($user)->public()->createOne(['file_name' => 'public.png']);
        Storage::disk('public')->put($public->getPath() ?? '', 'content');

        $response = $this->getJson('/api/v1/media');

        assertSuccessResponse($response, 200);

        $data = $response->json('data');
        throw_unless(is_array($data), RuntimeException::class, 'List data was not an array.');

        $byId = collect($data)->keyBy('id');
        $privateData = $byId->get($private->id);
        $publicData = $byId->get($public->id);
        throw_unless(is_array($privateData) && is_array($publicData), RuntimeException::class, 'List items were not arrays.');

        expect($publicData['url'])->toContain('/storage/')
            ->and($privateData['url'])->toBeNull();
    });

    it('keeps private and public urls correct with sparse fields', function (): void {
        $user = loginAsUser();
        $private = MediaFactory::new()->forModel($user)->createOne(['file_name' => 'private.png']);
        $public = MediaFactory::new()->forModel($user)->public()->createOne(['file_name' => 'public.png']);
        Storage::disk('public')->put($public->getPath() ?? '', 'content');

        $response = $this->getJson('/api/v1/media?fields[media]=id,name');

        assertSuccessResponse($response, 200);

        $data = $response->json('data');
        throw_unless(is_array($data), RuntimeException::class, 'List data was not an array.');

        $byId = collect($data)->keyBy('id');
        $privateData = $byId->get($private->id);
        $publicData = $byId->get($public->id);
        throw_unless(is_array($privateData) && is_array($publicData), RuntimeException::class, 'List items were not arrays.');

        expect($publicData['url'])->toContain('/storage/')
            ->and($privateData['url'])->toBeNull();
    });

    it('still mints a signed url on show with expires', function (): void {
        $user = loginAsUser();
        $media = MediaFactory::new()->forModel($user)->createOne();

        $response = $this->getJson("/api/v1/media/{$media->id}?expires=10");

        assertSuccessResponse($response, 200);
        expect($response->json('data.url'))->toContain('/api/v1/media/'.$media->id.'/file')
            ->and($response->json('data.url'))->toContain('signature=');
    });
});
