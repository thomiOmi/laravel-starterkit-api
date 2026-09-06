<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Media\Contracts\HasMedia;
use Modules\Media\Jobs\ProcessMediaJob;
use Modules\Media\Support\FileAdder;
use Modules\Media\Traits\InteractsWithMedia;

covers(FileAdder::class);

describe('Media file adder parity', function () {
    beforeEach(function () {
        Storage::fake('public');
    });

    function limitedOwner(): Model&HasMedia
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
                $this->addMediaCollection('gallery')->onlyKeepLatest(3);
            }
        };

        $owner->forceFill(['id' => (string) Str::ulid()]);
        $owner->exists = true;

        return $owner;
    }

    it('keeps only the latest items of a limited collection', function () {
        $owner = limitedOwner();

        for ($i = 0; $i < 4; $i++) {
            $owner->addMedia(UploadedFile::fake()->image("photo{$i}.jpg", 20, 20))->toMediaCollection('gallery');
        }

        expect($owner->getMedia('gallery'))->toHaveCount(3);
    });

    it('rejects a non-positive collection limit', function () {
        $owner = limitedOwner();

        expect(fn (): mixed => $owner->addMediaCollection('broken')->onlyKeepLatest(0))
            ->toThrow(InvalidArgumentException::class);
    });

    it('forces queued processing per call', function () {
        config(['media.queue' => false]);
        Bus::fake([ProcessMediaJob::class]);

        $owner = limitedOwner();

        $owner->addMedia(UploadedFile::fake()->image('photo.jpg', 20, 20))
            ->onQueue()
            ->toMediaCollection('gallery');

        Bus::assertDispatched(ProcessMediaJob::class);
    });

    it('toggles responsive generation per call', function () {
        config(['media.responsive.widths' => [32]]);

        $owner = limitedOwner();

        $with = $owner->addMedia(UploadedFile::fake()->image('a.jpg', 100, 80))
            ->withResponsiveImagesIf(true)
            ->toMediaCollection('gallery');

        expect($with->fresh()?->responsive_images)->not->toBe([]);

        $without = $owner->addMedia(UploadedFile::fake()->image('b.jpg', 100, 80))
            ->withResponsiveImagesIf(fn (): bool => false)
            ->toMediaCollection('gallery');

        expect($without->fresh()?->responsive_images)->toBe([]);
    });

    it('accepts custom headers per call', function () {
        $owner = limitedOwner();

        $media = $owner->addMedia(UploadedFile::fake()->image('photo.jpg', 20, 20))
            ->addCustomHeaders(['CacheControl' => 'max-age=60'])
            ->toMediaCollection('gallery');

        expect($media->exists)->toBeTrue();
        Storage::disk('public')->assertExists($media->getPath() ?? '');
    });
});
