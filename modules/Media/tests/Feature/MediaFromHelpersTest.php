<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Media\Traits\InteractsWithMedia;

describe('Media from helpers', function () {
    beforeEach(function () {
        Storage::fake('public');
    });

    it('adds media from string', function () {
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
        DB::table('users')->insert([
            'id' => $owner->getKey(),
            'name' => 'Test',
            'email' => 'test-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $media = $owner->addMediaFromString('hello world', 'hello.txt')->toMediaCollection('documents');

        expect($media->original_name)->toBe('hello.txt')
            ->and(Storage::disk('public')->exists($media->path))->toBeTrue();
    });

    it('uses usingFileName and sanitizingFileName', function () {
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
        DB::table('users')->insert([
            'id' => $owner->getKey(),
            'name' => 'Test2',
            'email' => 'test2-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $file = UploadedFile::fake()->image('photo.jpg', 50, 50);
        $media = $owner->addMedia($file)
            ->usingFileName('custom.jpg')
            ->sanitizingFileName(fn (string $name): string => 'sanitized-'.$name)
            ->withCustomProperties(['alt' => 'test'])
            ->withManipulations(['filter' => 'grayscale'])
            ->toMediaCollection('default');

        expect($media->custom_properties)->toBeArray()
            ->and($media->custom_properties['alt'] ?? null)->toBe('test')
            ->and(is_array($media->custom_properties['manipulations'] ?? null) ? $media->custom_properties['manipulations']['filter'] : null)->toBe('grayscale')
            ->and(Storage::disk('public')->exists($media->path))->toBeTrue();
    });

    it('preservingOriginal skips image processing', function () {
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
        DB::table('users')->insert([
            'id' => $owner->getKey(),
            'name' => 'Test3',
            'email' => 'test3-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $file = UploadedFile::fake()->image('photo.jpg', 100, 100);
        $media = $owner->addMedia($file)->preservingOriginal()->toMediaCollection('default');

        // Preserving original should keep original mime (image/jpeg) not convert to webp
        expect($media->mime_type)->toBe('image/jpeg');
    });

    it('adds media from url', function () {
        Http::fake([
            'https://example.com/image.jpg' => Http::response('fake-image-content', 200, ['Content-Type' => 'image/jpeg']),
        ]);

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
        DB::table('users')->insert([
            'id' => $owner->getKey(),
            'name' => 'Test4',
            'email' => 'test4-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $media = $owner->addMediaFromUrl('https://example.com/image.jpg')->toMediaCollection('default');

        expect($media->original_name)->toBe('image.jpg');
    });
});
