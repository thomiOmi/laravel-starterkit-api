<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;

test('has user permissions', function () {
    expect(PermissionEnum::UserView->value)->toBe('user.view');
    expect(PermissionEnum::UserCreate->value)->toBe('user.create');
    expect(PermissionEnum::UserEdit->value)->toBe('user.edit');
    expect(PermissionEnum::UserDelete->value)->toBe('user.delete');
    expect(PermissionEnum::UserRestore->value)->toBe('user.restore');
});

test('has role permissions', function () {
    expect(PermissionEnum::RoleView->value)->toBe('role.view');
    expect(PermissionEnum::RoleCreate->value)->toBe('role.create');
    expect(PermissionEnum::RoleEdit->value)->toBe('role.edit');
    expect(PermissionEnum::RoleDelete->value)->toBe('role.delete');
});

test('has permission permissions', function () {
    expect(PermissionEnum::PermissionView->value)->toBe('permission.view');
    expect(PermissionEnum::PermissionCreate->value)->toBe('permission.create');
    expect(PermissionEnum::PermissionEdit->value)->toBe('permission.edit');
    expect(PermissionEnum::PermissionDelete->value)->toBe('permission.delete');
});

test('is backed by string', function () {
    expect(PermissionEnum::tryFrom('user.view'))->toBe(PermissionEnum::UserView);
    expect(PermissionEnum::tryFrom('invalid'))->toBeNull();
});
