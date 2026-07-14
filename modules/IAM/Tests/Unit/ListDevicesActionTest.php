<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use Illuminate\Contracts\Pagination\Paginator;
use Modules\IAM\Actions\ListDevicesAction;
use Modules\IAM\Models\User;

describe('ListDevicesAction', function () {
    it('returns paginated tokens for the given user', function () {
        $user = User::factory()->create();
        $user->createToken('device-a');
        $action = app(ListDevicesAction::class);

        $result = $action->handle($user);

        expect($result)->toBeInstanceOf(Paginator::class);
        expect($result->items())->toHaveCount(1);
    });

    it('returns an empty paginator when user has no tokens', function () {
        $user = User::factory()->create();
        $action = app(ListDevicesAction::class);

        $result = $action->handle($user);

        expect($result)->toBeInstanceOf(Paginator::class);
        expect($result->items())->toBeEmpty();
    });
});
