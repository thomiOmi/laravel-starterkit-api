<?php

declare(strict_types=1);

use App\Contracts\AvatarResolver;
use Illuminate\Support\Facades\Storage;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\Media\Database\Factories\MediaFactory;
use Modules\Media\Services\MediaAvatarResolver;

covers(MediaAvatarResolver::class);

describe('MediaAvatarResolver', function () {
    beforeEach(function () {
        Storage::fake('public');
    });

    it('resolves the url for the owners avatar media', function () {
        $user = UserFactory::new()->createOne();
        $media = MediaFactory::new()->forUser($user)->inCollection('avatars')->createOne();

        $url = app(AvatarResolver::class)->resolve($media->id, $user);

        expect($url)->toBe(Storage::disk('public')->url($media->path));
    });

    it('throws when media belongs to another user', function () {
        $user = UserFactory::new()->createOne();
        $media = MediaFactory::new()->inCollection('avatars')->createOne();

        expect(fn () => app(AvatarResolver::class)->resolve($media->id, $user))
            ->toThrow(InvalidArgumentException::class);
    });

    it('throws when the media is not found', function () {
        $user = UserFactory::new()->createOne();

        expect(fn () => app(AvatarResolver::class)->resolve('01AAAAAAAAAAAAAAAAAAAAAAAA', $user))
            ->toThrow(InvalidArgumentException::class);
    });

    it('throws when stored on a foreign disk', function () {
        $user = UserFactory::new()->createOne();
        $media = MediaFactory::new()->forUser($user)->inCollection('avatars')->createOne();
        $media->forceFill(['disk' => 's3'])->save();

        expect(fn () => app(AvatarResolver::class)->resolve($media->id, $user))
            ->toThrow(InvalidArgumentException::class);
    });

    it('throws outside the avatars collection', function () {
        $user = UserFactory::new()->createOne();
        $media = MediaFactory::new()->forUser($user)->createOne();

        expect(fn () => app(AvatarResolver::class)->resolve($media->id, $user))
            ->toThrow(InvalidArgumentException::class);
    });
});
