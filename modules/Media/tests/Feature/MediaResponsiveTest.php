<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Media\Actions\GenerateResponsiveImagesAction;
use Modules\Media\Contracts\HasMedia;
use Modules\Media\Database\Factories\MediaFactory;
use Modules\Media\Enums\MediaVisibilityEnum;
use Modules\Media\Jobs\ProcessMediaJob;
use Modules\Media\Services\MediaConversionService;
use Modules\Media\Traits\InteractsWithMedia;

covers(GenerateResponsiveImagesAction::class);

describe('Media responsive images', function () {
    beforeEach(function () {
        Storage::fake('public');
        config(['media.responsive.widths' => [32, 64, 2000]]);
    });

    function responsiveOwner(): Model&HasMedia
    {
        $owner = new class extends Model implements HasMedia
        {
            use InteractsWithMedia;

            protected $table = 'users';

            public $incrementing = false;

            protected $keyType = 'string';

            protected $primaryKey = 'id';

            public function registerMediaCollections(): void
            {
                $this->addMediaCollection('gallery')->withResponsiveImages();
            }
        };

        $ownerId = (string) Str::ulid();
        $owner->forceFill(['id' => $ownerId]);
        $owner->exists = true;

        // The responsive flag is resolved through a fresh model lookup,
        // so the owner row must exist like InteractsWithMediaTest does.
        DB::table('users')->insert([
            'id' => $ownerId,
            'name' => 'Responsive',
            'email' => 'responsive-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $owner;
    }

    it('generates capped widths and fills the responsive json', function () {
        $owner = responsiveOwner();

        $media = $owner->addMedia(UploadedFile::fake()->image('photo.jpg', 100, 80))
            ->toMediaCollection('gallery');

        $responsive = $media->fresh()?->responsive_images;

        expect($responsive)->toBeArray()
            ->and(array_keys(is_array($responsive) ? $responsive : []))->toBe([32, 64]);

        foreach ([32, 64] as $width) {
            $path = is_array($responsive) ? ($responsive[$width]['path'] ?? null) : null;

            expect($path)->toBeString();
            Storage::disk('public')->assertExists(is_string($path) ? $path : '');
        }
    });

    it('stays empty when the collection did not opt in', function () {
        $user = loginAsUser();

        $media = $user->addMedia(UploadedFile::fake()->image('photo.jpg', 100, 80))
            ->toMediaCollection('default');

        expect($media->fresh()?->responsive_images)->toBe([]);
    });

    it('builds a sorted srcset for public media', function () {
        $owner = responsiveOwner();

        $media = $owner->addMedia(UploadedFile::fake()->image('photo.jpg', 100, 80))
            ->toMediaCollection('gallery');

        $media->update(['visibility' => MediaVisibilityEnum::Public]);

        $srcset = $media->fresh()?->getSrcset();

        expect($srcset)->toBeString()
            ->and($srcset ?? '')->toContain('32w')
            ->and($srcset ?? '')->toContain('64w');
    });

    it('returns null srcset for private media', function () {
        $owner = responsiveOwner();

        $media = $owner->addMedia(UploadedFile::fake()->image('photo.jpg', 100, 80))
            ->toMediaCollection('gallery');

        expect($media->fresh()?->getSrcset())->toBeNull();
    });

    it('dispatches the job instead of generating inline when queued', function () {
        config(['media.queue' => true]);
        Bus::fake([ProcessMediaJob::class]);

        $owner = responsiveOwner();

        $owner->addMedia(UploadedFile::fake()->image('photo.jpg', 100, 80))
            ->toMediaCollection('gallery');

        Bus::assertDispatched(ProcessMediaJob::class);
    });

    it('generates responsive images when the queued job runs', function () {
        $owner = responsiveOwner();

        $media = MediaFactory::new()->forModel($owner, 'gallery')->createOne(['mime_type' => 'image/jpeg']);
        Storage::disk('public')->put($media->getPath() ?? '', (string) UploadedFile::fake()->image('seed.jpg', 100, 80)->getContent());

        (new ProcessMediaJob((string) $media->id))->handle(app(MediaConversionService::class), app(GenerateResponsiveImagesAction::class));

        expect($media->fresh()?->responsive_images)->not->toBe([]);
    });
});
