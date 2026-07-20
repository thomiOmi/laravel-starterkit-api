<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use Modules\IAM\Actions\ShowUserAction;
use Modules\IAM\Models\User;

describe('ShowUserAction', function () {
    it('returns the user with roles and permissions eager-loaded', function () {
        $user = User::factory()->create();
        $action = app(ShowUserAction::class);

        $result = $action->handle($user);

        expect($result)->toBeInstanceOf(User::class)
            ->id->toBe($user->id);
        expect($result->relationLoaded('roles'))->toBeTrue();
        expect($result->relationLoaded('permissions'))->toBeTrue();
    });
});
