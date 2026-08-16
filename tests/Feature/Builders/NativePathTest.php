<?php

declare(strict_types=1);

namespace Tests\Feature\Builders;

use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Models\User;

describe('native path for custom query builders', function (): void {
    it('allows plain Eloquent queries on models with a custom builder attached', function (): void {
        $userA = UserFactory::new()->createOne(['email' => 'native-path-a@example.com']);
        UserFactory::new()->createOne(['email' => 'native-path-b@example.com']);

        $result = User::query()
            ->where('email', $userA->email)
            ->first();

        expect($result?->getKey())->toBe($userA->getKey());
    });

    it('allows plain Eloquent where clauses without going through the builder whitelist', function (): void {
        $userA = UserFactory::new()->createOne(['email' => 'native-path-c@example.com']);
        UserFactory::new()->createOne(['email' => 'native-path-d@example.com']);

        $count = User::where('email', 'like', 'native-path-%')->count();

        expect($count)->toBe(2)
            ->and(User::whereKey($userA->getKey())->exists())->toBeTrue();
    });

    it('supports native scopes and eager loading alongside the custom builder', function (): void {
        $user = UserFactory::new()->createOne(['email' => 'native-path-e@example.com']);

        $loaded = User::query()
            ->with('roles')
            ->whereKey($user->getKey())
            ->firstOrFail();

        expect($loaded->relationLoaded('roles'))->toBeTrue();
    });
});
