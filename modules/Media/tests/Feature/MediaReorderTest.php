<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;
use Modules\Media\Actions\ReorderMediaAction;
use Modules\Media\Database\Factories\MediaFactory;
use Modules\Media\Traits\InteractsWithMedia;

covers([InteractsWithMedia::class, ReorderMediaAction::class]);

describe('reorderMedia', function (): void {
    beforeEach(function (): void {
        Storage::fake('public');
        Storage::fake('local');
    });

    it('does nothing when the ordered list is empty', function (): void {
        $user = loginAsUser();
        $media = MediaFactory::new()->forModel($user, 'avatars')->createOne(['order_column' => 4]);

        $user->reorderMedia('avatars', []);

        expect($media->fresh()?->order_column)->toBe(4);
    });

    it('renumbers order_column from one following the given order', function (): void {
        $user = loginAsUser();

        $first = MediaFactory::new()->forModel($user, 'avatars')->createOne(['order_column' => 1]);
        $second = MediaFactory::new()->forModel($user, 'avatars')->createOne(['order_column' => 2]);
        $third = MediaFactory::new()->forModel($user, 'avatars')->createOne(['order_column' => 3]);

        $user->reorderMedia('avatars', [$third->id, $first->id, $second->id]);

        expect($third->fresh()?->order_column)->toBe(1)
            ->and($first->fresh()?->order_column)->toBe(2)
            ->and($second->fresh()?->order_column)->toBe(3);
    });

    it('rejects an id that does not exist', function (): void {
        $user = loginAsUser();
        $media = MediaFactory::new()->forModel($user, 'avatars')->createOne(['order_column' => 1]);

        expect(fn () => $user->reorderMedia('avatars', ['01AAAAAAAAAAAAAAAAAAAAAAAA']))
            ->toThrow(InvalidArgumentException::class)
            ->and($media->fresh()?->order_column)->toBe(1);
    });

    it('rejects an id belonging to a different collection of the same model', function (): void {
        $user = loginAsUser();

        $avatar = MediaFactory::new()->forModel($user, 'avatars')->createOne(['order_column' => 1]);
        $document = MediaFactory::new()->forModel($user, 'default')->createOne(['order_column' => 2]);

        expect(fn () => $user->reorderMedia('avatars', [$document->id]))
            ->toThrow(InvalidArgumentException::class)
            ->and($avatar->fresh()?->order_column)->toBe(1)
            ->and($document->fresh()?->order_column)->toBe(2);
    });

    it('rejects an id owned by a different model', function (): void {
        $user = loginAsUser();
        $stranger = loginAsUser();

        $mine = MediaFactory::new()->forModel($user, 'avatars')->createOne(['order_column' => 1]);
        $theirs = MediaFactory::new()->forModel($stranger, 'avatars')->createOne(['order_column' => 2]);

        expect(fn () => $user->reorderMedia('avatars', [$theirs->id]))
            ->toThrow(InvalidArgumentException::class)
            ->and($mine->fresh()?->order_column)->toBe(1)
            ->and($theirs->fresh()?->order_column)->toBe(2);
    });

    it('leaves every order_column untouched when one id in the list is rejected', function (): void {
        $user = loginAsUser();
        $stranger = loginAsUser();

        $first = MediaFactory::new()->forModel($user, 'avatars')->createOne(['order_column' => 1]);
        $second = MediaFactory::new()->forModel($user, 'avatars')->createOne(['order_column' => 2]);
        $theirs = MediaFactory::new()->forModel($stranger, 'avatars')->createOne(['order_column' => 3]);

        expect(fn () => $user->reorderMedia('avatars', [$second->id, $theirs->id, $first->id]))
            ->toThrow(InvalidArgumentException::class)
            ->and($first->fresh()?->order_column)->toBe(1)
            ->and($second->fresh()?->order_column)->toBe(2)
            ->and($theirs->fresh()?->order_column)->toBe(3);
    });
});
