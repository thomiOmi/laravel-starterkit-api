<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use Modules\IAM\Actions\DeleteUserAction;
use Modules\IAM\Models\User;

describe('DeleteUserAction', function () {
    it('deletes an existing user', function () {
        $user = User::factory()->create();
        $action = app(DeleteUserAction::class);

        expect($action->handle($user))->toBeTrue();
        expect($user->fresh()->trashed())->toBeTrue();
    });

    it('returns true when model is already soft-deleted', function () {
        $user = User::factory()->create();
        $user->delete();
        $action = app(DeleteUserAction::class);

        expect($action->handle($user))->toBeTrue();
    });
});
