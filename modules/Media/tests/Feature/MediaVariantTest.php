<?php

declare(strict_types=1);

use App\Enums\MediaVisibilityEnum;
use App\Enums\PermissionEnum;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\IAM\Models\Permission;
use Modules\IAM\Models\User;
use Modules\Media\Http\Controllers\V1\MediaVariantController;
use Modules\Media\Models\Media;

covers(MediaVariantController::class);

describe('GET /api/v1/media/{media}/variant', function () {
    beforeEach(function () {
        Storage::fake('public');
        Permission::firstOrCreate(['name' => PermissionEnum::MediaView->value, 'guard_name' => 'sanctum']);
    });

    /**
     * Persist a media row plus real decodable bytes on the fake public disk.
     */
    function seedImageMedia(User $owner, int $width = 200, int $height = 100): Media
    {
        $media = Media::query()->create([
            'collection_name' => 'default',
            'disk' => 'public',
            'mime_type' => 'image/jpeg',
            'size' => 0,
            'path' => 'default/'.fake()->unique()->md5().'.jpg',
            'visibility' => MediaVisibilityEnum::Private->value,
            'meta' => ['original_name' => 'seed.jpg'],
            'uploaded_by' => $owner->id,
        ]);

        $file = UploadedFile::fake()->image('seed.jpg', $width, $height);
        Storage::disk('public')->put($media->path, (string) $file->getContent());

        return $media;
    }

    it('serves a resized webp variant with long-lived cache headers', function () {
        $user = loginAsUser();
        $media = seedImageMedia($user);

        $response = $this->getJson("/api/v1/media/{$media->id}/variant?w=32");

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

        $response = $this->getJson("/api/v1/media/{$media->id}/variant?w=64&format=jpg");

        $response->assertOk();
        expect($response->headers->get('Content-Type'))->toContain('image/jpeg');
    });

    it('returns 304 when the etag still matches', function () {
        $user = loginAsUser();
        $media = seedImageMedia($user);

        $first = $this->getJson("/api/v1/media/{$media->id}/variant?w=48");
        $etag = (string) $first->headers->get('ETag');

        $second = $this->getJson("/api/v1/media/{$media->id}/variant?w=48", ['If-None-Match' => $etag]);

        $second->assertStatus(304);
        expect($second->headers->get('ETag'))->toBe($etag)
            ->and((string) $second->getContent())->toBeEmpty();
    });

    it('never upscales beyond the original dimensions', function () {
        $user = loginAsUser();
        $media = seedImageMedia($user, width: 50, height: 40);

        $response = $this->getJson("/api/v1/media/{$media->id}/variant?w=2000");

        $response->assertOk();
        $image = imagecreatefromstring((string) $response->getContent());
        if (! $image instanceof GdImage) {
            throw new RuntimeException('The variant response is not a decodable image.');
        }
        expect(imagesx($image))->toBe(50)
            ->and(imagesy($image))->toBe(40);
    });

    it('rejects out-of-bounds widths and unsupported formats', function (array $query, string $field) {
        $user = loginAsUser();
        $media = seedImageMedia($user);

        $response = $this->getJson("/api/v1/media/{$media->id}/variant?".http_build_query($query));

        assertProblemResponse($response, 422, 'validation');
        $response->assertJsonValidationErrors([$field]);
    })->with([
        'width below minimum' => [['w' => 31], 'w'],
        'width above maximum' => [['w' => 2001], 'w'],
        'missing width' => [[], 'w'],
        'gif output' => [['w' => 64, 'format' => 'gif'], 'format'],
    ]);

    it('rejects non-image media with a problem response', function () {
        $user = loginAsUser();
        $media = Media::query()->create([
            'collection_name' => 'default',
            'disk' => 'public',
            'mime_type' => 'application/pdf',
            'size' => 9,
            'path' => 'default/'.fake()->unique()->md5().'.pdf',
            'visibility' => MediaVisibilityEnum::Private->value,
            'meta' => ['original_name' => 'doc.pdf'],
            'uploaded_by' => $user->id,
        ]);
        Storage::disk('public')->put($media->path, "%PDF-1.4\n");

        $response = $this->getJson("/api/v1/media/{$media->id}/variant?w=64");

        assertProblemResponse($response, 422, 'validation');
    });

    it('forbids viewers without ownership or the view permission', function () {
        $owner = loginAsUser();
        seedImageMedia($owner);
        // Switch the acting user to someone unrelated to the media row.
        loginAsUser();

        $media = Media::query()->where('uploaded_by', $owner->id)->sole();
        $response = $this->getJson("/api/v1/media/{$media->id}/variant?w=64");

        assertProblemResponse($response, 403);
    });

    it('allows staff with the view permission to read foreign media', function () {
        $owner = loginAsUser();
        $media = seedImageMedia($owner);
        $staff = loginAsUser();
        $staff->givePermissionTo(PermissionEnum::MediaView->value);

        $response = $this->getJson("/api/v1/media/{$media->id}/variant?w=40");

        $response->assertOk();
    });

    it('rejects unauthenticated requests', function () {
        $this->getJson('/api/v1/media/01AAAAAAAAAAAAAAAAAAAAAAAA/variant?w=64')->assertUnauthorized();
    });

    it('returns 404 for unknown media', function () {
        loginAsUser();

        $this->getJson('/api/v1/media/01AAAAAAAAAAAAAAAAAAAAAAAA/variant?w=64')->assertNotFound();
    });
});
