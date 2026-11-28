<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
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

describe('animated media in a converting collection', function (): void {
    beforeEach(function (): void {
        Storage::fake('public');
        Storage::fake('local');
        Event::fake([MessageLogged::class, MediaProcessingFailed::class]);
    });

    function convertingOwnerForAnimation(): Model&HasMedia
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
            'name' => 'Animated owner',
            'email' => 'animated-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $owner->setAttribute('id', $ownerId);
        $owner->exists = true;

        return $owner;
    }

    function tinyAnimatedGif(): string
    {
        $header = (string) hex2bin('47494638396101000100800000000000ffffff');
        $frame = (string) hex2bin('2c0000000001000100000202440100');

        return $header.$frame.$frame.hex2bin('3b');
    }

    function tinyAnimatedWebp(): string
    {
        $base64 = 'UklGRuQAAABXRUJQVlA4WAoAAAASAAAAHwAAHwAAQU5JTQYAAAD/////AABBTk1GWAAAAAAAAAAAAB8AAB8AACgAAABWUDggQAAAAFADAJ0BKiAAIAA+kUKcSiWjoqGoCACwEgllAMaqgABAURwAAP7u09/+47A3Xi3/+5wP+5wP+5wP42ykJ2cKAABBTk1GWAAAAAAAAAAAAB8AAB8AACgAAABWUDggQAAAAFADAJ0BKiAAIAA+kUKcSiWjoqGoCACwEgllAMaqgABAURwAAP7u09/+47A3Xi3/+5wP+5wP+5wP42ykJ2cKAAA=';

        return base64_decode($base64, true);
    }

    function pngWithActlBytes(): string
    {
        $png = (string) UploadedFile::fake()->image('still.png', 32, 32)->getContent();
        $unpacked = unpack('N', substr($png, 8, 4));
        $length = is_array($unpacked) ? ($unpacked[1] ?? null) : null;
        throw_unless(is_int($length), RuntimeException::class, 'IHDR length missing.');

        $data = pack('NNN', 2, 0, 0);
        $chunk = pack('N', strlen($data)).'acTL'.$data.pack('N', crc32('acTL'.$data));

        return substr($png, 0, 20 + $length).$chunk.substr($png, 20 + $length);
    }

    it('stays processed without conversions for animated formats', function (string $mime, string $extension, string $bytes): void {
        $owner = convertingOwnerForAnimation();
        $media = MediaFactory::new()->forModel($owner, 'gallery')->createOne([
            'mime_type' => $mime,
            'file_name' => 'probe.'.$extension,
            'processing_status' => 'pending',
        ]);
        Storage::disk($media->disk)->put($media->getPath() ?? '', $bytes);

        new ProcessMediaJob((string) $media->id)->handle(
            resolve(MediaConversionService::class),
            resolve(GenerateResponsiveImagesAction::class),
        );

        $processed = $media->fresh();
        throw_unless($processed instanceof Media, RuntimeException::class, 'Processed media was not found.');

        expect($processed->processing_status->value)->toBe('processed')
            ->and($processed->processing_error)->toBeNull()
            ->and($processed->conversions()->count())->toBe(0);

        Event::assertNotDispatched(MediaProcessingFailed::class);
    })->with([
        'animated gif' => ['image/gif', 'gif', tinyAnimatedGif()],
        'animated png' => ['image/png', 'png', pngWithActlBytes()],
        'animated webp' => ['image/webp', 'webp', tinyAnimatedWebp()],
    ]);

    it('still marks a genuinely broken png as failed', function (): void {
        $owner = convertingOwnerForAnimation();
        $valid = (string) UploadedFile::fake()->image('seed.png', 32, 32)->getContent();
        $media = MediaFactory::new()->forModel($owner, 'gallery')->createOne([
            'mime_type' => 'image/png',
            'file_name' => 'broken.png',
            'processing_status' => 'pending',
        ]);
        Storage::disk($media->disk)->put($media->getPath() ?? '', substr($valid, 0, 40));

        new ProcessMediaJob((string) $media->id)->handle(
            resolve(MediaConversionService::class),
            resolve(GenerateResponsiveImagesAction::class),
        );

        $failed = $media->fresh();
        throw_unless($failed instanceof Media, RuntimeException::class, 'Failed media was not found.');

        expect($failed->processing_status->value)->toBe('failed')
            ->and($failed->processing_error)->toContain('thumb');
    });
});
