<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use App\Enums\RoleEnum;
use Modules\IAM\Actions\CreateUserAction;
use Modules\IAM\Models\Role;
use Modules\IAM\Models\User;
use Modules\IAM\Payloads\V1\UserPayload;

beforeEach(function () {
    Role::create(['name' => RoleEnum::User->value, 'guard_name' => 'sanctum']);
});

describe('CreateUserAction', function () {
    it('creates a new user and assigns the default role', function () {
        $action = app(CreateUserAction::class);

        $user = $action->handle(new UserPayload(
            name: 'Test User',
            email: 'test@create.com',
            password: 'secret123',
        ));

        expect($user)->toBeInstanceOf(User::class)
            ->name->toBe('Test User')
            ->email->toBe('test@create.com');

        expect($user->hasRole(RoleEnum::User))->toBeTrue();
    });
});
