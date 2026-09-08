<?php

declare(strict_types=1);

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Media\Actions\AttachMediaAction;
use Modules\Media\Contracts\HasMedia;
use Modules\Media\Traits\InteractsWithMedia;

covers(AttachMediaAction::class);

describe('Media attach authorization', function () {
    beforeEach(function () {
        Storage::fake('public');
        Storage::fake('local');
    });

    function attachOwner(): Model&HasMedia
    {
        $owner = new class extends Model implements HasMedia
        {
            use InteractsWithMedia;

            protected $table = 'users';

            public $incrementing = false;

            protected $keyType = 'string';

            protected $primaryKey = 'id';
        };

        $owner->forceFill(['id' => (string) Str::ulid()]);
        $owner->exists = true;

        return $owner;
    }

    it('allows the owner to reassign media', function () {
        $owner = loginAsUser();
        $other = loginAsUser();

        $media = $owner->addMedia(UploadedFile::fake()->image('photo.jpg', 20, 20))->toMediaCollection('default');

        $this->actingAs($owner);

        $moved = app(AttachMediaAction::class)->handle($media, $other);

        expect($moved->model_id)->toBe($other->getKey());
    });

    it('forbids strangers from reassigning media', function () {
        $owner = attachOwner();

        $media = $owner->addMedia(UploadedFile::fake()->image('photo.jpg', 20, 20))->toMediaCollection('default');

        loginAsUser();

        expect(fn (): mixed => app(AttachMediaAction::class)->handle($media, attachOwner()))
            ->toThrow(AuthorizationException::class);
    });
});
