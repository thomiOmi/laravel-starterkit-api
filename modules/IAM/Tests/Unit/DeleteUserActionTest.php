<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use Illuminate\Contracts\Auth\Guard;
use Modules\IAM\Actions\DeleteUserAction;
use Modules\IAM\Models\User;

describe('DeleteUserAction', function () {
    it('deletes an existing user', function () {
        $user = User::factory()->create();
        $action = new DeleteUserAction(app(Guard::class));

        expect($action->handle($user->id))->toBeTrue();
        expect($user->fresh()->trashed())->toBeTrue();
    });

    it('returns false when deleting self', function () {
        $user = User::factory()->create();
        $guard = app(Guard::class);
        $guard->setUser($user);
        $action = new DeleteUserAction($guard);

        expect($action->handle($user->id))->toBeFalse();
    });

    it('returns false for a non-existent user', function () {
        $user = User::factory()->create();
        $guard = app(Guard::class);
        $guard->setUser($user);
        $action = new DeleteUserAction($guard);

        expect($action->handle('999999'))->toBeFalse();
    });
});
