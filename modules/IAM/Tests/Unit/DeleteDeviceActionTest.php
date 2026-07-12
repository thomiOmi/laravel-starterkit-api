<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\IAM\Actions\DeleteDeviceAction;
use Modules\IAM\Models\User;

describe('DeleteDeviceAction', function () {
    it('deletes a token owned by the user', function () {
        $user = User::factory()->create();
        $token = $user->createToken('test-device');
        $action = app(DeleteDeviceAction::class);

        $action->handle($user, (string) $token->accessToken->id);

        expect($user->tokens()->count())->toBe(0);
    });

    it('throws ModelNotFoundException for a non-existent token', function () {
        $user = User::factory()->create();
        $action = app(DeleteDeviceAction::class);

        $action->handle($user, '999999');
    })->throws(ModelNotFoundException::class);
});
