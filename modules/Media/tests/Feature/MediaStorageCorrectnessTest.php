<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Media\Contracts\HasMedia;
use Modules\Media\Models\Media;
use Modules\Media\Traits\InteractsWithMedia;

describe('Media storage correctness', function (): void {
    beforeEach(function (): void {
        Storage::fake('public');
        Storage::fake('local');
        config(['media.queue' => false]);
    });

    function correctnessOwner(): Model&HasMedia
    {
        $owner = new #[Table(name: 'users', key: 'id', keyType: 'string')] #[WithoutIncrementing] class extends Model implements HasMedia
        {
            use InteractsWithMedia;

            public function registerMediaCollections(): void
            {
                $this->addMediaCollection('gallery')->singleFile();
            }

            public function registerMediaConversions(?Media $media = null): void
            {
                $this->addMediaConversion('thumbnail')
                    ->width(32)
                    ->height(32)
                    ->fit('cover')
                    ->format('webp')
                    ->quality(80)
                    ->performOnCollections('gallery');
            }
        };

        $ownerId = (string) Str::ulid();
        $owner->forceFill(['id' => $ownerId]);
        $owner->exists = true;

        DB::table('users')->insert([
            'id' => $ownerId,
            'name' => 'Correctness',
            'email' => 'correctness-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $owner;
    }

    it('removes old conversions and resets derived json on single-file replacement', function (): void {
        $owner = correctnessOwner();

        $first = $owner->addMedia(UploadedFile::fake()->image('first.jpg', 100, 100))->toMediaCollection('gallery');
        $oldConversion = $first->conversions()->where('name', 'thumbnail')->firstOrFail();
        $oldOriginal = $first->getPath();

        $second = $owner->addMedia(UploadedFile::fake()->image('second.jpg', 100, 100))->toMediaCollection('gallery');

        expect(Media::query()->where('model_id', $owner->getKey())->where('collection_name', 'gallery')->count())->toBe(1)
            ->and($second->conversions()->count())->toBe(1)
            ->and($second->responsive_images)->toBe([]);

        Storage::disk($oldConversion->disk)->assertMissing($oldConversion->path);
        Storage::disk($second->disk)->assertMissing(is_string($oldOriginal) ? $oldOriginal : '');
        expect(Media::query()->whereKey($oldConversion->id)->exists())->toBeFalse();
    });

    it('returns the database conversion path from getPath', function (): void {
        $owner = correctnessOwner();

        $media = $owner->addMedia(UploadedFile::fake()->image('photo.jpg', 100, 100))->toMediaCollection('gallery');
        $conversion = $media->conversions()->where('name', 'thumbnail')->firstOrFail();

        expect($media->getPath('thumbnail'))->toBe($conversion->path)
            ->and($media->getPath('missing'))->toBeNull();
    });

    it('does not leak the per-call disk into later uploads', function (): void {
        Storage::fake('s3');
        $owner = correctnessOwner();

        $first = $owner->addMedia(UploadedFile::fake()->image('first.jpg', 20, 20))
            ->withDisk('s3')
            ->toMediaCollection('documents');

        expect($first->disk)->toBe('s3');

        $second = $owner->addMedia(UploadedFile::fake()->image('second.jpg', 20, 20))
            ->toMediaCollection('documents');

        expect($second->disk)->toBe('local');
        Storage::disk('local')->assertExists($second->getPath() ?? '');
        Storage::disk('s3')->assertMissing($second->getPath() ?? '');
    });
});
