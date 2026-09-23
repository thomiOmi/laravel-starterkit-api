<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
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
use Modules\Media\Models\Media;
use Modules\Media\Services\MediaConversionService;
use Modules\Media\Traits\InteractsWithMedia;

covers(GenerateResponsiveImagesAction::class);

describe('Media responsive images', function (): void {
    beforeEach(function (): void {
        Storage::fake('public');
        Storage::fake('local');
        config(['media.responsive.widths' => [32, 64, 2000]]);
    });

    function responsiveOwner(): Model&HasMedia
    {
        $owner = new #[Table(name: 'users', key: 'id', keyType: 'string')] #[WithoutIncrementing] class extends Model implements HasMedia
        {
            use InteractsWithMedia;

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

    it('generates capped widths and fills the responsive json', function (): void {
        $owner = responsiveOwner();

        $media = $owner->addMedia(UploadedFile::fake()->image('photo.jpg', 100, 80))
            ->toMediaCollection('gallery');

        $responsive = $media->fresh()?->responsive_images;

        expect($responsive)->toBeArray()
            ->and(array_keys(is_array($responsive) ? $responsive : []))->toBe([32, 64]);

        foreach ([32, 64] as $width) {
            $path = is_array($responsive) ? ($responsive[$width]['path'] ?? null) : null;

            expect($path)->toBeString();
            Storage::disk($media->disk)->assertExists(is_string($path) ? $path : '');
        }
    });

    it('reads originals from the source disk and writes responsive files to the conversion disk', function (): void {
        Storage::fake('attachments');
        $owner = responsiveOwner();
        $media = MediaFactory::new()->forModel($owner, 'gallery')->createOne([
            'mime_type' => 'image/jpeg',
            'conversions_disk' => 'attachments',
        ]);
        Storage::disk('public')->put($media->getPath() ?? '', (string) UploadedFile::fake()->image('source.jpg', 100, 80)->getContent());

        resolve(GenerateResponsiveImagesAction::class)->handle($media);

        $responsive = $media->fresh()?->responsive_images;
        $path = is_array($responsive) ? ($responsive[32]['path'] ?? null) : null;

        expect($path)->toBeString()
            ->and(Storage::disk('attachments')->exists(is_string($path) ? $path : ''))->toBeTrue()
            ->and(Storage::disk('public')->exists(is_string($path) ? $path : ''))->toBeFalse();
    });

    it('stays empty when the collection did not opt in', function (): void {
        $user = loginAsUser();

        $media = $user->addMedia(UploadedFile::fake()->image('photo.jpg', 100, 80))
            ->toMediaCollection('default');

        expect($media->fresh()?->responsive_images)->toBe([]);
    });

    it('builds a sorted srcset for public media', function (): void {
        $owner = responsiveOwner();

        $media = $owner->addMedia(UploadedFile::fake()->image('photo.jpg', 100, 80))
            ->toMediaCollection('gallery');

        $media->update(['visibility' => MediaVisibilityEnum::Public]);

        $srcset = $media->fresh()?->getSrcset();

        expect($srcset)->toBeString()
            ->and($srcset ?? '')->toContain('32w')
            ->and($srcset ?? '')->toContain('64w');
    });

    it('returns null srcset for private media', function (): void {
        $owner = responsiveOwner();

        $media = $owner->addMedia(UploadedFile::fake()->image('photo.jpg', 100, 80))
            ->toMediaCollection('gallery');

        expect($media->fresh()?->getSrcset())->toBeNull();
    });

    it('dispatches the job instead of generating inline when queued', function (): void {
        config(['media.queue' => true]);
        Bus::fake([ProcessMediaJob::class]);

        $owner = responsiveOwner();

        $owner->addMedia(UploadedFile::fake()->image('photo.jpg', 100, 80))
            ->toMediaCollection('gallery');

        Bus::assertDispatched(ProcessMediaJob::class);
        $queuedMedia = Media::query()->where('collection_name', 'gallery')->sole();
        expect($queuedMedia->processing_status->value)->toBe('pending');
    });

    it('generates responsive images when the queued job runs', function (): void {
        $owner = responsiveOwner();

        $media = MediaFactory::new()->forModel($owner, 'gallery')->createOne(['mime_type' => 'image/jpeg']);
        Storage::disk('public')->put($media->getPath() ?? '', (string) UploadedFile::fake()->image('seed.jpg', 100, 80)->getContent());

        new ProcessMediaJob((string) $media->id)->handle(resolve(MediaConversionService::class), resolve(GenerateResponsiveImagesAction::class));

        $processedMedia = $media->fresh();
        throw_unless($processedMedia instanceof Media, RuntimeException::class, 'Processed media was not found.');

        expect($processedMedia->responsive_images)->not->toBe([])
            ->and($processedMedia->processing_status->value)->toBe('processed')
            ->and($processedMedia->processed_at)->not->toBeNull();
    });

    it('marks queued processing as failed when the source file is missing', function (): void {
        $owner = responsiveOwner();
        $media = MediaFactory::new()->forModel($owner, 'gallery')->createOne(['mime_type' => 'image/jpeg']);

        expect(function () use ($media): void {
            new ProcessMediaJob((string) $media->id)->handle(resolve(MediaConversionService::class), resolve(GenerateResponsiveImagesAction::class));
        })
            ->toThrow(RuntimeException::class);

        $failed = $media->fresh();
        expect($failed?->processing_status->value)->toBe('failed')
            ->and($failed?->processing_error)->toBe('The source media file is missing.')
            ->and($failed?->processed_at)->toBeNull();
    });
});
