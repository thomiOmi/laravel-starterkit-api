<?php

declare(strict_types=1);

use App\Enums\UserStatusEnum;

covers(UserStatusEnum::class);

describe('UserStatusEnum', function () {

    it('has Active', fn () => expect(UserStatusEnum::Active->value)->toBe('active'));
    it('has Inactive', fn () => expect(UserStatusEnum::Inactive->value)->toBe('inactive'));
    it('has Pending', fn () => expect(UserStatusEnum::Pending->value)->toBe('pending'));
    it('has Suspended', fn () => expect(UserStatusEnum::Suspended->value)->toBe('suspended'));
    it('has Banned', fn () => expect(UserStatusEnum::Banned->value)->toBe('banned'));

});
