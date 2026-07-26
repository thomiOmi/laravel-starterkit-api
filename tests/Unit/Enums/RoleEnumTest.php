<?php

declare(strict_types=1);

use App\Enums\RoleEnum;

test('has super admin role', function () {
    expect(RoleEnum::SuperAdmin->value)->toBe('super-admin');
});

test('has admin role', function () {
    expect(RoleEnum::Admin->value)->toBe('admin');
});

test('has user role', function () {
    expect(RoleEnum::User->value)->toBe('user');
});

test('is backed by string', function () {
    expect(RoleEnum::tryFrom('admin'))->toBe(RoleEnum::Admin);
    expect(RoleEnum::tryFrom('invalid'))->toBeNull();
});
