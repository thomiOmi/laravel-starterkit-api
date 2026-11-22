<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use Pest\Expectation;

covers(RoleEnum::class);

describe('RoleEnum', function (): void {

    it('has SuperAdmin', fn (): Expectation => expect(RoleEnum::SuperAdmin->value)->toBe('super-admin'));
    it('has Admin', fn (): Expectation => expect(RoleEnum::Admin->value)->toBe('admin'));
    it('has User', fn (): Expectation => expect(RoleEnum::User->value)->toBe('user'));

    describe('label', function (): void {
        it('returns the English label by default', function (): void {
            expect(RoleEnum::SuperAdmin->label())->toBe('Super Admin')
                ->and(RoleEnum::Admin->label())->toBe('Admin')
                ->and(RoleEnum::User->label())->toBe('User');
        });

        it('returns the Indonesian label in the id locale', function (): void {
            app()->setLocale('id');

            expect(RoleEnum::SuperAdmin->label())->toBe('Super Admin')
                ->and(RoleEnum::Admin->label())->toBe('Admin')
                ->and(RoleEnum::User->label())->toBe('Pengguna');
        });
    });

});
