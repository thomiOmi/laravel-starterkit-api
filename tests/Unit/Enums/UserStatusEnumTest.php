<?php

declare(strict_types=1);

use App\Enums\UserStatusEnum;

test('has active status', function () {
    expect(UserStatusEnum::Active->value)->toBe('active');
});

test('has inactive status', function () {
    expect(UserStatusEnum::Inactive->value)->toBe('inactive');
});

test('has pending status', function () {
    expect(UserStatusEnum::Pending->value)->toBe('pending');
});

test('has suspended status', function () {
    expect(UserStatusEnum::Suspended->value)->toBe('suspended');
});

test('has banned status', function () {
    expect(UserStatusEnum::Banned->value)->toBe('banned');
});

test('is backed by string', function () {
    expect(UserStatusEnum::tryFrom('pending'))->toBe(UserStatusEnum::Pending);
    expect(UserStatusEnum::tryFrom('invalid'))->toBeNull();
});
