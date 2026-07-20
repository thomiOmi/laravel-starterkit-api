<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use Modules\IAM\Actions\DeleteDeviceAction;
use Modules\IAM\Models\User;

describe('DeleteDeviceAction', function () {
    it('deletes a token owned by the user', function () {
        $user = User::factory()->create();
        $token = $user->createToken('test-device');
        $action = app(DeleteDeviceAction::class);

        $action->handle($user, $token->accessToken);

        expect($user->tokens()->count())->toBe(0);
    });
});
