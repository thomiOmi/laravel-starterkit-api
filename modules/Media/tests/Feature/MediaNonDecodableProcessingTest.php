<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Media\Actions\GenerateResponsiveImagesAction;
use Modules\Media\Contracts\HasMedia;
use Modules\Media\Database\Factories\MediaFactory;
use Modules\Media\Events\MediaProcessingFailed;
use Modules\Media\Jobs\ProcessMediaJob;
use Modules\Media\Models\Media;
use Modules\Media\Services\MediaConversionService;
use Modules\Media\Traits\InteractsWithMedia;

describe('formats with no decodable still image', function (): void {
    beforeEach(function (): void {
        Storage::fake('public');
        Storage::fake('local');
        Event::fake([MessageLogged::class, MediaProcessingFailed::class]);
    });

    /**
     * An owner whose gallery collection has a conversion, so the job really
     * resolves a configured conversion for each probed media item.
     */
    function convertingOwner(): Model&HasMedia
    {
        $owner = new #[Table(name: 'users', key: 'id', keyType: 'string')] #[WithoutIncrementing] class extends Model implements HasMedia
        {
            use InteractsWithMedia;

            public function registerMediaConversions(?Media $media = null): void
            {
                $this->addMediaConversion('thumb')->width(320);
            }
        };

        $ownerId = (string) Str::ulid();
        DB::table('users')->insert([
            'id' => $ownerId,
            'name' => 'Converting owner',
            'email' => 'converting-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $owner->setAttribute('id', $ownerId);
        $owner->exists = true;

        return $owner;
    }

    it('stays processed without a conversion error', function (string $mime, string $extension, string $fixture, int $expectedConversions): void {
        $owner = convertingOwner();
        $media = MediaFactory::new()->forModel($owner, 'gallery')->createOne([
            'mime_type' => $mime,
            'file_name' => 'probe.'.$extension,
            'processing_status' => 'pending',
        ]);
        Storage::disk($media->disk)->put($media->getPath() ?? '', (string) file_get_contents($fixture));

        new ProcessMediaJob((string) $media->id)->handle(
            resolve(MediaConversionService::class),
            resolve(GenerateResponsiveImagesAction::class),
        );

        $processed = $media->fresh();
        throw_unless($processed instanceof Media, RuntimeException::class, 'Processed media was not found.');

        expect($processed->processing_status->value)->toBe('processed')
            ->and($processed->processing_error)->toBeNull()
            ->and($processed->conversions()->count())->toBe($expectedConversions);

        Event::assertNotDispatched(MediaProcessingFailed::class);
    })->with([
        'heic' => ['image/heic', 'heic', 'C:/Users/thomi/Downloads/sample-media/images/heic/heic-sample.heic', 0],
        'heif' => ['image/heif', 'heif', 'C:/Users/thomi/Downloads/sample-media/images/heic/heic-sample.heic', 0],
        'avif' => ['image/avif', 'avif', 'C:/Users/thomi/Downloads/sample-media/images/avif/avif-sample1.avif', (gd_info()['AVIF Support'] ?? false) ? 1 : 0],
    ]);
});
