<?php

declare(strict_types=1);

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Media\Actions\AttachMediaAction;
use Modules\Media\Contracts\HasMedia;
use Modules\Media\Traits\InteractsWithMedia;

covers(AttachMediaAction::class);

describe('Media attach authorization', function (): void {
    beforeEach(function (): void {
        Storage::fake('public');
        Storage::fake('local');
    });

    function attachOwner(): Model&HasMedia
    {
        $owner = new #[Table(name: 'users', key: 'id', keyType: 'string')] #[WithoutIncrementing] class extends Model implements HasMedia
        {
            use InteractsWithMedia;
        };

        $owner->forceFill(['id' => (string) Str::ulid()]);
        $owner->exists = true;

        return $owner;
    }

    it('allows the owner to reassign media', function (): void {
        $owner = loginAsUser();
        $other = loginAsUser();

        $media = $owner->addMedia(UploadedFile::fake()->image('photo.jpg', 20, 20))->toMediaCollection('default');

        $this->actingAs($owner);

        // Caller (Request/Controller) authorizes before invoking the pure Action.
        Gate::authorize('update', $media);

        $moved = resolve(AttachMediaAction::class)->handle($media, $other);

        expect($moved->model_id)->toBe($other->getKey());
    });

    it('forbids strangers from reassigning media', function (): void {
        $owner = attachOwner();

        $media = $owner->addMedia(UploadedFile::fake()->image('photo.jpg', 20, 20))->toMediaCollection('default');

        loginAsUser();

        expect(fn (): mixed => Gate::authorize('update', $media))
            ->toThrow(AuthorizationException::class);

        // Pure Action no longer authorizes itself; policy is the source of truth.
        expect(Gate::allows('update', $media))->toBeFalse();
    });
});
