<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Media\Actions\GenerateResponsiveImagesAction;
use Modules\Media\Contracts\HasMedia;
use Modules\Media\Models\Media;
use Modules\Media\Services\MediaConversionService;
use Modules\Media\Traits\InteractsWithMedia;

/**
 * AVIF bytes are produced by GD when the driver supports the format. HEIC has
 * no encoder here, so the fixture uses the same ftyp header the upload tests
 * use; the point is to record what the conversion pipeline actually does.
 */
function mediaFormatOwner(): Model&HasMedia
{
    $owner = new #[Table(name: 'users', key: 'id', keyType: 'string')] #[WithoutIncrementing] class extends Model implements HasMedia
    {
        use InteractsWithMedia;

        public function registerMediaCollections(): void
        {
            $this->addMediaCollection('gallery')->withResponsiveImages();
        }

        public function registerMediaConversions(?Media $media = null): void
        {
            $this->addMediaConversion('thumbnail')
                ->width(32)
                ->format('webp')
                ->performOnCollections('gallery');
        }
    };

    $ownerId = (string) Str::ulid();

    DB::table('users')->insert([
        'id' => $ownerId,
        'name' => 'Format owner',
        'email' => 'format-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $owner->setAttribute('id', $ownerId);
    $owner->exists = true;

    return $owner;
}

function mediaFormatBytes(string $extension): string
{
    if ($extension === 'avif') {
        $image = imagecreatetruecolor(64, 64);
        ob_start();
        imageavif($image, null, 80);
        imagedestroy($image);

        $buffer = ob_get_clean();

        return is_string($buffer) ? $buffer : '';
    }

    return "\x00\x00\x00\x18ftypheic\x00\x00\x00\x00heicmif1".str_repeat("\x00", 64);
}

describe('AVIF and HEIC conversion pipeline (observed behavior)', function (): void {
    beforeEach(function (): void {
        Storage::fake('public');
        Storage::fake('local');
        config(['media.responsive.widths' => [32]]);
    });

    it('currently generates AVIF conversions and responsive siblings with GD', function (): void {
        $owner = mediaFormatOwner();

        $media = $owner->addMedia(UploadedFile::fake()->createWithContent('still.avif', mediaFormatBytes('avif')))
            ->toMediaCollection('gallery');

        $conversions = resolve(MediaConversionService::class)->generate($media);
        $responsive = resolve(GenerateResponsiveImagesAction::class)->handle($media);
        $stored = Media::query()->findOrFail($media->id);

        expect($stored->mime_type)->toBe('image/avif')
            ->and($conversions)->toHaveKey('thumbnail')
            ->and($responsive)->toHaveKey(32);
    });

    it('currently skips HEIC conversions and logs the failure', function (): void {
        $warnings = [];

        Log::shouldReceive('warning')->andReturnUsing(
            function (string $message, array $context) use (&$warnings): void {
                $warnings[] = ['message' => $message, 'context' => $context];
            },
        );

        $owner = mediaFormatOwner();

        $media = $owner->addMedia(UploadedFile::fake()->createWithContent('still.heic', mediaFormatBytes('heic')))
            ->toMediaCollection('gallery');

        $conversions = resolve(MediaConversionService::class)->generate($media);
        $responsive = resolve(GenerateResponsiveImagesAction::class)->handle($media);
        $stored = Media::query()->findOrFail($media->id);

        expect($stored->mime_type)->toBe('image/heic')
            ->and($conversions)->toBeEmpty()
            ->and($responsive)->toBeEmpty()
            ->and($warnings)->not->toBeEmpty()
            ->and($warnings[0]['context']['media_id'])->toBe($media->id);
    });
});
