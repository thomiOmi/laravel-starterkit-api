<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Media\Database\Factories\MediaFactory;
use Modules\Media\Models\Media;
use Modules\Media\Traits\InteractsWithMedia;

covers(InteractsWithMedia::class);

describe('InteractsWithMedia', function () {
    beforeEach(function () {
        Storage::fake('public');
    });

    it('provides media relationship and collection helpers', function () {
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

        // Create media via factory forModel
        $media = MediaFactory::new()->forModel($owner, 'avatars')->createOne([
            'order_column' => 1,
        ]);
        MediaFactory::new()->forModel($owner, 'avatars')->createOne([
            'order_column' => 2,
        ]);
        MediaFactory::new()->forModel($owner, 'documents')->createOne();

        expect($owner->getMedia('avatars'))->toHaveCount(2)
            ->and($owner->getFirstMedia('avatars')?->id)->toBe($media->id)
            ->and($owner->getMedia('documents'))->toHaveCount(1)
            ->and($owner->getMedia('missing'))->toBeEmpty()
            ->and($owner->getFirstMedia('missing'))->toBeNull();
    });

    it('adds media via pending builder', function () {
        Storage::fake('public');

        $owner = new class extends Model
        {
            use InteractsWithMedia;

            protected $table = 'users';

            public $incrementing = false;

            protected $keyType = 'string';

            protected $primaryKey = 'id';
        };

        $ownerId = (string) Str::ulid();
        $owner->forceFill(['id' => $ownerId]);
        $owner->exists = true;

        // Ensure owner exists in DB for FK-like association (users table)
        DB::table('users')->insert([
            'id' => $ownerId,
            'name' => 'Test',
            'email' => 'test-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

        $media = $owner->addMedia($file)
            ->usingName('custom-name.jpg')
            ->withCustomProperties(['alt' => 'Test'])
            ->toMediaCollection('avatars');

        expect($media->model_type)->toBe($owner->getMorphClass())
            ->and($media->model_id)->toBe($ownerId)
            ->and($media->collection_name)->toBe('avatars')
            ->and($media->original_name)->toBe('custom-name.jpg')
            ->and($media->custom_properties)->toMatchArray(['alt' => 'Test']);

        Storage::disk('public')->assertExists($media->getPath() ?? '');
    });

    it('returns url helpers', function () {
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

        $public = MediaFactory::new()->forModel($owner, 'avatars')->public()->createOne();
        Storage::disk('public')->put($public->getPath() ?? '', 'content');

        $private = MediaFactory::new()->forModel($owner, 'documents')->createOne();
        Storage::disk('public')->put($private->getPath() ?? '', 'content');

        expect($owner->getFirstMediaUrl('avatars'))->toBe(Storage::disk('public')->url($public->getPath() ?? ''))
            ->and($owner->getFirstMediaUrl('documents'))->toBeNull()
            ->and($owner->getFirstMediaSignedUrl('documents'))->toContain('/api/v1/media/')
            ->and($owner->getFirstMediaSignedUrl('missing'))->toBeNull();
    });

    it('clears a media collection', function () {
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

        MediaFactory::new()->forModel($owner, 'avatars')->count(2)->create();
        MediaFactory::new()->forModel($owner, 'documents')->createOne();

        $deleted = $owner->clearMediaCollection('avatars');

        expect($deleted)->toBe(2)
            ->and($owner->getMedia('avatars'))->toBeEmpty()
            ->and($owner->getMedia('documents'))->toHaveCount(1);
    });
});
