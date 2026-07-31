<?php

declare(strict_types=1);

use App\Enums\RoleEnum;

covers(RoleEnum::class);

describe('RoleEnum', function () {

    it('has SuperAdmin', fn () => expect(RoleEnum::SuperAdmin->value)->toBe('super-admin'));
    it('has Admin', fn () => expect(RoleEnum::Admin->value)->toBe('admin'));
    it('has User', fn () => expect(RoleEnum::User->value)->toBe('user'));

    it('is backed by string', function () {
        expect(RoleEnum::tryFrom('admin'))->toBe(RoleEnum::Admin);
    });

    it('returns null for unknown values', function (string $unknownValue) {
        expect(RoleEnum::tryFrom($unknownValue))->toBeNull();
    })->with(['invalid', 'moderator', 'super']);

});
