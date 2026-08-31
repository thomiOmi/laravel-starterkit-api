<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Media\Database\Factories\MediaFactory;
use Modules\Media\Http\Controllers\V1\MediaVariantController;
use Modules\Media\Models\Media;

covers(MediaVariantController::class);

describe('GET /api/v1/media/{media}/s/{modifiers}', function () {
    beforeEach(function () {
        Storage::fake('public');
        DB::table('permissions')->insertOrIgnore([
            'id' => (string) Str::ulid(),
            'name' => PermissionEnum::MediaView->value,
            'guard_name' => 'sanctum',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    });

    /**
     * Persist a media row plus real decodable bytes on the fake public disk.
     */
    function seedImageMedia(Model $owner, int $width = 200, int $height = 100): Media
    {
        /** @var Media $media */
        $media = MediaFactory::new()->forModel($owner)->createOne(['mime_type' => 'image/jpeg']);

        $file = UploadedFile::fake()->image('seed.jpg', $width, $height);
        Storage::disk('public')->put($media->path, (string) $file->getContent());

        return $media;
    }

    it('serves a resized webp variant with long-lived cache headers', function () {
        $user = loginAsUser();
        $media = seedImageMedia($user);

        $response = $this->getJson("/api/v1/media/{$media->id}/s/32");

        $response->assertOk();
        expect($response->headers->get('Content-Type'))->toContain('image/webp')
            ->and($response->headers->get('Cache-Control'))->toContain('max-age=31536000')
            ->and($response->headers->get('Cache-Control'))->toContain('public')
            ->and($response->headers->get('ETag'))->toBeString();

        $image = imagecreatefromstring((string) $response->getContent());
        if (! $image instanceof GdImage) {
            throw new RuntimeException('The variant response is not a decodable image.');
        }
        expect(imagesx($image))->toBe(32)
            ->and(imagesy($image))->toBeLessThanOrEqual(100);
    });

    it('honours the requested jpg format', function () {
        $user = loginAsUser();
        $media = seedImageMedia($user);

        $response = $this->getJson("/api/v1/media/{$media->id}/s/64/f/jpg");

        $response->assertOk();
        expect($response->headers->get('Content-Type'))->toContain('image/jpeg');
    });

    it('parses s/320x200 shorthand', function () {
        $user = loginAsUser();
        $media = seedImageMedia($user, width: 100, height: 100);

        $response = $this->getJson("/api/v1/media/{$media->id}/s/320x200");

        $response->assertOk();
        $image = imagecreatefromstring((string) $response->getContent());
        if (! $image instanceof GdImage) {
            throw new RuntimeException('The variant response is not a decodable image.');
        }
        // Should be scaled to fit within 320x200, not upscaled beyond 100x100
        expect(imagesx($image))->toBeLessThanOrEqual(100);
    });

    it('returns 304 when the etag still matches', function () {
        $user = loginAsUser();
        $media = seedImageMedia($user);

        $first = $this->getJson("/api/v1/media/{$media->id}/s/48");
        $etag = (string) $first->headers->get('ETag');

        $second = $this->getJson("/api/v1/media/{$media->id}/s/48", ['If-None-Match' => $etag]);

        $second->assertStatus(304);
        expect($second->headers->get('ETag'))->toBe($etag)
            ->and((string) $second->getContent())->toBeEmpty();
    });

    it('never upscales beyond the original dimensions', function () {
        $user = loginAsUser();
        $media = seedImageMedia($user, width: 50, height: 40);

        $response = $this->getJson("/api/v1/media/{$media->id}/s/2000");

        $response->assertOk();
        $image = imagecreatefromstring((string) $response->getContent());
        if (! $image instanceof GdImage) {
            throw new RuntimeException('The variant response is not a decodable image.');
        }
        expect(imagesx($image))->toBe(50)
            ->and(imagesy($image))->toBe(40);
    });

    it('caches the generated variant on disk and serves later requests from it', function () {
        $user = loginAsUser();
        $media = seedImageMedia($user);

        $first = $this->getJson("/api/v1/media/{$media->id}/s/32");
        $first->assertOk();

        expect(Storage::disk('public')->allFiles('variants/'.$media->id))->toHaveCount(1);

        // Remove the original to prove the next response comes from the cache.
        Storage::disk('public')->delete($media->path);

        $second = $this->getJson("/api/v1/media/{$media->id}/s/32");
        $second->assertOk();
        expect($second->headers->get('ETag'))->toBe($first->headers->get('ETag'))
            ->and(Storage::disk('public')->exists($media->path))->toBeFalse()
            ->and(Storage::disk('public')->allFiles('variants/'.$media->id))->toHaveCount(1);
    });

    it('rejects out-of-bounds widths and unsupported formats', function (string $modifiers) {
        $user = loginAsUser();
        $media = seedImageMedia($user);

        $response = $this->getJson("/api/v1/media/{$media->id}/s/{$modifiers}");

        assertProblemResponse($response, 422, 'validation');
    })->with([
        'width below minimum' => '31',
        'width above maximum' => '2001',
        'gif output' => '64/f/gif',
    ]);

    it('rejects non-image media with a problem response', function () {
        $user = loginAsUser();
        $media = MediaFactory::new()->forModel($user)->createOne(['mime_type' => 'application/pdf']);
        Storage::disk('public')->put($media->path, "%PDF-1.4\n");

        $response = $this->getJson("/api/v1/media/{$media->id}/s/64");

        assertProblemResponse($response, 422, 'validation');
    });

    it('forbids viewers without ownership or the view permission', function () {
        $owner = loginAsUser();
        seedImageMedia($owner);
        loginAsUser();

        $media = Media::query()->where('model_id', $owner->id)->sole();
        $response = $this->getJson("/api/v1/media/{$media->id}/s/64");

        assertProblemResponse($response, 403);
    });

    it('allows staff with the view permission to read foreign media', function () {
        $owner = loginAsUser();
        $media = seedImageMedia($owner);
        $staff = loginAsUser();
        $staff->givePermissionTo(PermissionEnum::MediaView->value);

        $response = $this->getJson("/api/v1/media/{$media->id}/s/40");

        $response->assertOk();
    });

    it('rejects unauthenticated requests', function () {
        $this->getJson('/api/v1/media/01AAAAAAAAAAAAAAAAAAAAAAAA/s/64')->assertUnauthorized();
    });

    it('returns 404 for unknown media', function () {
        loginAsUser();

        $this->getJson('/api/v1/media/01AAAAAAAAAAAAAAAAAAAAAAAA/s/64')->assertNotFound();
    });
});
