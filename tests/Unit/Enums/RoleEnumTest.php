<?php

declare(strict_types=1);

use App\Enums\RoleEnum;

covers(RoleEnum::class);

describe('RoleEnum', function () {

    it('has SuperAdmin', fn () => expect(RoleEnum::SuperAdmin->value)->toBe('super-admin'));
    it('has Admin', fn () => expect(RoleEnum::Admin->value)->toBe('admin'));
    it('has User', fn () => expect(RoleEnum::User->value)->toBe('user'));

    describe('label', function () {
        it('returns the English label by default', function () {
            expect(RoleEnum::SuperAdmin->label())->toBe('Super Admin')
                ->and(RoleEnum::Admin->label())->toBe('Admin')
                ->and(RoleEnum::User->label())->toBe('User');
        });

        it('returns the Indonesian label in the id locale', function () {
            app()->setLocale('id');

            expect(RoleEnum::SuperAdmin->label())->toBe('Super Admin')
                ->and(RoleEnum::Admin->label())->toBe('Admin')
                ->and(RoleEnum::User->label())->toBe('Pengguna');
        });
    });

});
