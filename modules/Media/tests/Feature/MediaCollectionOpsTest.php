<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Media\Database\Factories\MediaConversionFactory;
use Modules\Media\Database\Factories\MediaFactory;
use Modules\Media\Models\MediaConversion;
use Modules\Media\Traits\InteractsWithMedia;

describe('Media collection ops', function () {
    beforeEach(function () {
        Storage::fake('public');
    });

    it('checks hasMedia', function () {
        $owner = new class extends Model
        {
            use InteractsWithMedia;

            protected $table = 'users';

            public $incrementing = false;

            protected $keyType = 'string';

            protected $primaryKey = 'id';
        };
        $owner->forceFill(['id' => (string) Str::ulid()]);
        $owner->exists = true;

        expect($owner->hasMedia('avatars'))->toBeFalse();

        MediaFactory::new()->forModel($owner, 'avatars')->createOne();

        expect($owner->hasMedia('avatars'))->toBeTrue()
            ->and($owner->hasMedia('documents'))->toBeFalse();
    });

    it('clears collection except given ids', function () {
        $owner = new class extends Model
        {
            use InteractsWithMedia;

            protected $table = 'users';

            public $incrementing = false;

            protected $keyType = 'string';

            protected $primaryKey = 'id';
        };
        $owner->forceFill(['id' => (string) Str::ulid()]);
        $owner->exists = true;

        $m1 = MediaFactory::new()->forModel($owner, 'gallery')->createOne();
        $m2 = MediaFactory::new()->forModel($owner, 'gallery')->createOne();
        $m3 = MediaFactory::new()->forModel($owner, 'gallery')->createOne();

        Storage::disk('public')->put($m1->path, 'a');
        Storage::disk('public')->put($m2->path, 'a');
        Storage::disk('public')->put($m3->path, 'a');

        $key = $m1->getKey();
        $m1Id = is_string($key) ? $key : (is_int($key) ? (string) $key : '');
        $deleted = $owner->clearMediaCollectionExcept('gallery', [$m1Id]);

        expect($deleted)->toBe(2)
            ->and($owner->getMedia('gallery'))->toHaveCount(1)
            ->and($owner->getMedia('gallery')->first()?->id)->toBe($m1->id);
    });

    it('returns first media url with conversion', function () {
        $owner = new class extends Model
        {
            use InteractsWithMedia;

            protected $table = 'users';

            public $incrementing = false;

            protected $keyType = 'string';

            protected $primaryKey = 'id';
        };
        $owner->forceFill(['id' => (string) Str::ulid()]);
        $owner->exists = true;

        $media = MediaFactory::new()->forModel($owner, 'avatars')->public()->createOne();
        Storage::disk('public')->put($media->path, 'content');

        // No conversion yet -> null for conversion url
        expect($owner->getFirstMediaUrl('avatars', 'thumbnail'))->toBeNull();

        // Create conversion
        /** @var MediaConversionFactory $factory */
        $factory = MediaConversion::factory();
        $conversion = $factory->forMedia($media, 'thumbnail')->createOne([
            'disk' => 'public',
            'path' => 'conversions/'.$media->id.'/thumbnail.webp',
        ]);
        Storage::disk('public')->put($conversion->path, 'thumb');

        expect($owner->getFirstMediaUrl('avatars', 'thumbnail'))->toBe(Storage::disk('public')->url($conversion->path))
            ->and($owner->getFirstMediaUrl('avatars'))->toBe(Storage::disk('public')->url($media->path));
    });
});
