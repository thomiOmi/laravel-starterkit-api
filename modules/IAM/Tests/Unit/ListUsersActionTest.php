<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use Modules\IAM\Actions\ListUsersAction;
use Modules\IAM\Filters\UserFilter;
use Modules\IAM\Models\User;

describe('ListUsersAction', function () {
    it('returns paginated users', function () {
        User::factory()->count(5)->create();
        $action = app(ListUsersAction::class);

        $result = $action->handle(app(UserFilter::class));

        expect($result->total())->toBe(5);
    });
});
