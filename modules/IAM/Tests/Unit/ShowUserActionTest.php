<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use Modules\IAM\Actions\ShowUserAction;
use Modules\IAM\Models\User;

describe('ShowUserAction', function () {
    it('finds an existing user by id', function () {
        $user = User::factory()->create();
        $action = app(ShowUserAction::class);

        $result = $action->handle($user->id);

        expect($result)->toBeInstanceOf(User::class)
            ->id->toBe($user->id);
    });

    it('returns null for a non-existent user', function () {
        $action = app(ShowUserAction::class);

        expect($action->handle('999999'))->toBeNull();
    });
});
