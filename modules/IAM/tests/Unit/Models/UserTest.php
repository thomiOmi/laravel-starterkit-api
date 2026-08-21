<?php

declare(strict_types=1);

use App\Enums\UserStatusEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Models\User;

covers(User::class);

uses(RefreshDatabase::class);

describe('User', function () {
    it('has expected fillable attributes', function () {
        expect((new User)->getFillable())->toContain('name', 'email', 'status', 'password', 'avatar');
    });

    it('hides password and remember_token', function () {
        expect((new User)->getHidden())->toContain('password', 'remember_token');
    });

    it('casts status to UserStatusEnum and password to hashed', function () {
        $casts = (new User)->getCasts();

        expect($casts['status'])->toBe(UserStatusEnum::class)
            ->and($casts['password'])->toBe('hashed')
            ->and($casts['email_verified_at'])->toBe('datetime');
    });

    it('reports hasPassword correctly', function () {
        $withPassword = UserFactory::new()->createOne(['password' => 'secret']);
        $withoutPassword = UserFactory::new()->social()->createOne();

        expect($withPassword->hasPassword())->toBeTrue()
            ->and($withoutPassword->hasPassword())->toBeFalse();
    });

    it('activates status when email becomes verified', function () {
        $user = UserFactory::new()->unverified()->createOne(['status' => UserStatusEnum::Pending]);

        $user->forceFill(['email_verified_at' => now()])->save();

        expect($user->fresh()?->status)->toBe(UserStatusEnum::Active);
    });

    it('has many social accounts', function () {
        $user = UserFactory::new()->createOne();
        $account = $user->socialAccounts()->create([
            'provider' => 'google',
            'provider_id' => '123',
        ]);

        expect($user->socialAccounts)->toHaveCount(1)
            ->and($account->user_id)->toBe($user->id);
    });
});
