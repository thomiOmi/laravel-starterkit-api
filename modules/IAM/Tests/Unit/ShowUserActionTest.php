<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\IAM\Actions\ShowUserAction;
use Modules\IAM\Models\User;

describe('ShowUserAction', function () {
    it('finds an existing user by id with roles and permissions eager-loaded', function () {
        $user = User::factory()->create();
        $action = app(ShowUserAction::class);

        $result = $action->handle($user->id);

        expect($result)->toBeInstanceOf(User::class)
            ->id->toBe($user->id);
        expect($result->relationLoaded('roles'))->toBeTrue();
        expect($result->relationLoaded('permissions'))->toBeTrue();
    });

    it('throws exception for a non-existent user', function () {
        $action = app(ShowUserAction::class);

        expect(fn () => $action->handle('999999'))->toThrow(ModelNotFoundException::class);
    });
});
