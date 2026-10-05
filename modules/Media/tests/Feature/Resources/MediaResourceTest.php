<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Media\Contracts\HasMedia;
use Modules\Media\Database\Factories\MediaConversionFactory;
use Modules\Media\Database\Factories\MediaFactory;
use Modules\Media\Http\Resources\MediaResource;
use Modules\Media\Traits\InteractsWithMedia;

covers(MediaResource::class);

describe('MediaResource conversion and fallback mapping', function (): void {
    beforeEach(function (): void {
        Storage::fake('public');
        Storage::fake('local');
    });

    /**
     * Persist an owner row whose 'gallery' collection declares both a
     * fallback url and a fallback path, then return a model instance that
     * can be used as the media owner.
     */
    function fallbackOwner(): Model&HasMedia
    {
        $owner = new #[Table(name: 'users', key: 'id', keyType: 'string')] #[WithoutIncrementing] class extends Model implements HasMedia
        {
            use InteractsWithMedia;

            public function registerMediaCollections(): void
            {
                $this->addMediaCollection('gallery')
                    ->useFallbackUrl('https://cdn.example.test/gallery.webp')
                    ->useFallbackPath('fallbacks/gallery.webp');
            }
        };

        $ownerId = (string) Str::ulid();
        $owner->forceFill(['id' => $ownerId]);
        $owner->exists = true;

        DB::table('users')->insert([
            'id' => $ownerId,
            'name' => 'Fallback',
            'email' => 'fallback-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $owner;
    }

    it('maps every conversion to its url keyed by conversion name', function (): void {
        $media = MediaFactory::new()->public()->createOne();

        MediaConversionFactory::new()->forMedia($media, 'thumbnail')->createOne();
        MediaConversionFactory::new()->forMedia($media, 'medium')->createOne();

        $media->load('conversions');

        $data = new MediaResource($media)->resolve(new Request);

        $conversions = $data['conversions'];
        throw_unless(is_array($conversions), RuntimeException::class, 'Conversions was not an array.');

        expect($conversions)->toHaveKeys(['thumbnail', 'medium'])
            ->and($conversions['thumbnail'])->toBeString()
            ->and($conversions['medium'])->toBeString();
    });

    it('maps conversions of private media to null because no url exists', function (): void {
        $media = MediaFactory::new()->createOne();

        MediaConversionFactory::new()->forMedia($media, 'thumbnail')->createOne();

        $media->load('conversions');

        $data = new MediaResource($media)->resolve(new Request);

        $conversions = $data['conversions'];
        throw_unless(is_array($conversions), RuntimeException::class, 'Conversions was not an array.');

        expect($conversions)->toHaveKey('thumbnail')
            ->and($conversions['thumbnail'])->toBeNull();
    });

    it('leaves conversions empty when none are registered', function (): void {
        $media = MediaFactory::new()->public()->createOne();

        $media->load('conversions');

        $data = new MediaResource($media)->resolve(new Request);

        expect($data['conversions'])->toBe([]);
    });

    it('exposes the fallback url and path declared by the owning collection', function (): void {
        $owner = fallbackOwner();

        $media = MediaFactory::new()->forModel($owner, 'gallery')->createOne();
        $media->load('model');

        $data = new MediaResource($media)->resolve(new Request);

        expect($data['fallback_url'])->toBe('https://cdn.example.test/gallery.webp')
            ->and($data['fallback_path'])->toBe('fallbacks/gallery.webp');
    });

    it('falls back to the collection url when private media has no url of its own', function (): void {
        $owner = fallbackOwner();

        $media = MediaFactory::new()->forModel($owner, 'gallery')->createOne();
        $media->load('model');

        $data = new MediaResource($media)->resolve(new Request);

        expect($data['url'])->toBe('https://cdn.example.test/gallery.webp');
    });

    it('leaves fallback fields null for a media whose collection declares none', function (): void {
        $owner = fallbackOwner();

        $media = MediaFactory::new()->forModel($owner, 'default')->createOne();
        $media->load('model');

        $data = new MediaResource($media)->resolve(new Request);

        expect($data['fallback_url'])->toBeNull()
            ->and($data['fallback_path'])->toBeNull();
    });

    it('prefers the pre-resolved url over the collection fallback', function (): void {
        $owner = fallbackOwner();

        $media = MediaFactory::new()->forModel($owner, 'gallery')->createOne();
        $media->load('model');

        $data = new MediaResource($media, 'https://signed.example.test/media')->resolve(new Request);

        expect($data['url'])->toBe('https://signed.example.test/media');
    });

    it('ignores a model_type that is not an eloquent model', function (): void {
        $media = MediaFactory::new()->createOne([
            'model_type' => Str::class,
            'model_id' => '1',
        ]);

        $data = new MediaResource($media)->resolve(new Request);

        expect($data['fallback_url'])->toBeNull()
            ->and($data['fallback_path'])->toBeNull()
            ->and($data['url'])->toBeNull();
    });
});
