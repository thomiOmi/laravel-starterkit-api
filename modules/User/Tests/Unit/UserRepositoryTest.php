<?php

declare(strict_types=1);

namespace Modules\User\Tests\Unit;

use Modules\User\Models\User;
use Modules\User\Repositories\UserRepository;

/**
 * Unit test for UserRepository.
 */
describe('UserRepository', function () {
    it('finds a user by id', function () {
        $user = User::factory()->create();
        $repo = app(UserRepository::class);

        $found = $repo->findById((string) $user->id);

        expect($found)->not->toBeNull()
            ->id->toBe($user->id);
    });
});
