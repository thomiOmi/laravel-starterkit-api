<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;

covers(PermissionEnum::class);

describe('PermissionEnum', function () {

    describe('user permissions', function () {
        it('has UserView', fn () => expect(PermissionEnum::UserView->value)->toBe('user.view'));
        it('has UserCreate', fn () => expect(PermissionEnum::UserCreate->value)->toBe('user.create'));
        it('has UserEdit', fn () => expect(PermissionEnum::UserEdit->value)->toBe('user.edit'));
        it('has UserDelete', fn () => expect(PermissionEnum::UserDelete->value)->toBe('user.delete'));
        it('has UserRestore', fn () => expect(PermissionEnum::UserRestore->value)->toBe('user.restore'));
    });

    describe('role permissions', function () {
        it('has RoleView', fn () => expect(PermissionEnum::RoleView->value)->toBe('role.view'));
        it('has RoleCreate', fn () => expect(PermissionEnum::RoleCreate->value)->toBe('role.create'));
        it('has RoleEdit', fn () => expect(PermissionEnum::RoleEdit->value)->toBe('role.edit'));
        it('has RoleDelete', fn () => expect(PermissionEnum::RoleDelete->value)->toBe('role.delete'));
    });

    describe('permission permissions', function () {
        it('has PermissionView', fn () => expect(PermissionEnum::PermissionView->value)->toBe('permission.view'));
        it('has PermissionCreate', fn () => expect(PermissionEnum::PermissionCreate->value)->toBe('permission.create'));
        it('has PermissionEdit', fn () => expect(PermissionEnum::PermissionEdit->value)->toBe('permission.edit'));
        it('has PermissionDelete', fn () => expect(PermissionEnum::PermissionDelete->value)->toBe('permission.delete'));
    });

    it('is backed by string', function () {
        expect(PermissionEnum::tryFrom('user.view'))->toBe(PermissionEnum::UserView);
    });

    it('returns null for unknown values', function (string $unknownValue) {
        expect(PermissionEnum::tryFrom($unknownValue))->toBeNull();
    })->with(['invalid', 'user.view-invalid', 'role.unknown']);

});
