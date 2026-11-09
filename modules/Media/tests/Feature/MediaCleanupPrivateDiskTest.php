<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Media\Console\Commands\MediaCleanupCommand;
use Modules\Media\Contracts\HasMedia;
use Modules\Media\Traits\InteractsWithMedia;

covers(MediaCleanupCommand::class);

describe('media:cleanup missing-file detection across disks', function (): void {
    beforeEach(function (): void {
        Storage::fake('public');
        Storage::fake('local');
        config(['media.queue' => false]);
    });

    function cleanupDiskOwner(): Model&HasMedia
    {
        $owner = new #[Table(name: 'users', key: 'id', keyType: 'string')] #[WithoutIncrementing] class extends Model implements HasMedia
        {
            use InteractsWithMedia;

            public function registerMediaCollections(): void
            {
                $this->addMediaCollection('avatars')->visibility('public');
                $this->addMediaCollection('contracts');
            }
        };

        $ownerId = (string) Str::ulid();
        $owner->setAttribute('id', $ownerId);
        $owner->exists = true;

        DB::table('users')->insert([
            'id' => $ownerId,
            'name' => 'Cleanup Disk',
            'email' => 'cleanup-disk-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $owner;
    }

    it('does not report public media whose file exists on the public disk', function (): void {
        $owner = cleanupDiskOwner();

        $media = $owner->addMedia(UploadedFile::fake()->image('avatar.jpg', 16, 16))
            ->toMediaCollection('avatars');

        expect($media->disk)->toBe('public');
        Storage::disk('public')->assertExists($media->getPath() ?? '');

        artisanCommand($this, 'media:cleanup')
            ->doesntExpectOutputToContain('Missing file')
            ->assertSuccessful();
    });

    it('does not report private media whose file exists on the private disk', function (): void {
        $owner = cleanupDiskOwner();

        $media = $owner->addMedia(UploadedFile::fake()->image('contract.jpg', 16, 16))
            ->toMediaCollection('contracts');

        expect($media->disk)->toBe('local');
        Storage::disk('local')->assertExists($media->getPath() ?? '');

        artisanCommand($this, 'media:cleanup')
            ->doesntExpectOutputToContain('Missing file')
            ->assertSuccessful();
    });

    it('reports public media whose file was removed from its disk', function (): void {
        $owner = cleanupDiskOwner();

        $media = $owner->addMedia(UploadedFile::fake()->image('removed.jpg', 16, 16))
            ->toMediaCollection('avatars');

        $path = $media->getPath();
        expect($path)->toBeString();

        Storage::disk($media->disk)->delete((string) $path);
        Storage::disk($media->disk)->assertMissing((string) $path);

        artisanCommand($this, 'media:cleanup')
            ->expectsOutputToContain('Missing file for media '.$media->id)
            ->assertSuccessful();
    });
});
