<?php

declare(strict_types=1);

use Modules\IAM\Actions\SocialUnlinkAction;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Models\SocialAccount;

covers(SocialUnlinkAction::class);

describe('SocialUnlinkAction', function (): void {
    it('unlinks social account when user has a password', function (): void {
        $user = UserFactory::new()->createOne(['password' => 'password123']);
        $account = SocialAccount::query()->create([
            'user_id' => $user->id,
            'provider' => 'github',
            'provider_id' => '12345',
        ]);

        $action = resolve(SocialUnlinkAction::class);
        $action->handle($user, 'github');

        expect(SocialAccount::query()->where('id', $account->id)->exists())->toBeFalse();
    });

    it('unlinks social account when passwordless user has multiple social accounts', function (): void {
        $user = UserFactory::new()->createOne(['password' => null]);
        $github = SocialAccount::query()->create([
            'user_id' => $user->id,
            'provider' => 'github',
            'provider_id' => '12345',
        ]);
        $google = SocialAccount::query()->create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => '67890',
        ]);

        $action = resolve(SocialUnlinkAction::class);
        $action->handle($user, 'github');

        expect(SocialAccount::query()->where('id', $github->id)->exists())->toBeFalse()
            ->and(SocialAccount::query()->where('id', $google->id)->exists())->toBeTrue();
    });

    it('throws when unlinking a provider that is not linked', function (): void {
        $user = UserFactory::new()->createOne();
        $action = resolve(SocialUnlinkAction::class);

        expect(fn () => $action->handle($user, 'google'))
            ->toThrow(InvalidArgumentException::class, __('validation.social_not_linked'));
    });

    it('throws when unlinking sole social account for a passwordless user', function (): void {
        $user = UserFactory::new()->createOne(['password' => null]);
        SocialAccount::query()->create([
            'user_id' => $user->id,
            'provider' => 'github',
            'provider_id' => '12345',
        ]);

        $action = resolve(SocialUnlinkAction::class);

        expect(fn () => $action->handle($user, 'github'))
            ->toThrow(InvalidArgumentException::class, __('auth.social_unlink_blocked'));
    });
});
