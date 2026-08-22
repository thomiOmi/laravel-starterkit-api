<?php

declare(strict_types=1);

use App\Contracts\MediaUrlResolver;
use App\Enums\MediaCollection;
use Illuminate\Support\Facades\Storage;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\Media\Database\Factories\MediaFactory;
use Modules\Media\Services\MediaUrlResolver as MediaUrlResolverService;

covers(MediaUrlResolverService::class);

describe('MediaUrlResolver', function () {
    beforeEach(function () {
        Storage::fake('public');
    });

    describe('forOwner', function () {
        it('resolves the url for owned media in the requested collection', function () {
            $user = UserFactory::new()->createOne();
            $media = MediaFactory::new()->forUser($user)->inCollection('avatars')->createOne();

            $url = app(MediaUrlResolver::class)->forOwner($media->id, $user, MediaCollection::Avatars->value);

            expect($url)->toBe(Storage::disk('public')->url($media->path));
        });

        it('throws when media belongs to another user', function () {
            $user = UserFactory::new()->createOne();
            $media = MediaFactory::new()->inCollection('avatars')->createOne();

            expect(fn () => app(MediaUrlResolver::class)->forOwner($media->id, $user, MediaCollection::Avatars->value))
                ->toThrow(InvalidArgumentException::class);
        });

        it('throws when the media is not found', function () {
            $user = UserFactory::new()->createOne();

            expect(fn () => app(MediaUrlResolver::class)->forOwner('01AAAAAAAAAAAAAAAAAAAAAAAA', $user, MediaCollection::Avatars->value))
                ->toThrow(InvalidArgumentException::class);
        });

        it('throws when stored on a foreign disk', function () {
            $user = UserFactory::new()->createOne();
            $media = MediaFactory::new()->forUser($user)->inCollection('avatars')->createOne();
            $media->forceFill(['disk' => 's3'])->save();

            expect(fn () => app(MediaUrlResolver::class)->forOwner($media->id, $user, MediaCollection::Avatars->value))
                ->toThrow(InvalidArgumentException::class);
        });

        it('throws outside the requested collection', function () {
            $user = UserFactory::new()->createOne();
            $media = MediaFactory::new()->forUser($user)->inCollection('documents')->createOne();

            expect(fn () => app(MediaUrlResolver::class)->forOwner($media->id, $user, MediaCollection::Avatars->value))
                ->toThrow(InvalidArgumentException::class);
        });
    });

    describe('public', function () {
        it('resolves publicly visible media regardless of owner', function () {
            $media = MediaFactory::new()->public()->createOne();

            $url = app(MediaUrlResolver::class)->public($media->id);

            expect($url)->toBe(Storage::disk('public')->url($media->path));
        });

        it('returns null for private media', function () {
            $media = MediaFactory::new()->createOne();

            expect(app(MediaUrlResolver::class)->public($media->id))->toBeNull();
        });

        it('returns null when the media is not found', function () {
            expect(app(MediaUrlResolver::class)->public('01AAAAAAAAAAAAAAAAAAAAAAAA'))->toBeNull();
        });
    });
});
