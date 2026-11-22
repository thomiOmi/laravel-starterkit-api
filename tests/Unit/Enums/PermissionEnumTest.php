<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use Pest\Expectation;

covers(PermissionEnum::class);

describe('PermissionEnum', function (): void {

    describe('user permissions', function (): void {
        it('has UserView', fn (): Expectation => expect(PermissionEnum::UserView->value)->toBe('user.view'));
        it('has UserCreate', fn (): Expectation => expect(PermissionEnum::UserCreate->value)->toBe('user.create'));
        it('has UserEdit', fn (): Expectation => expect(PermissionEnum::UserEdit->value)->toBe('user.edit'));
        it('has UserDelete', fn (): Expectation => expect(PermissionEnum::UserDelete->value)->toBe('user.delete'));
        it('has UserRestore', fn (): Expectation => expect(PermissionEnum::UserRestore->value)->toBe('user.restore'));
    });

    describe('role permissions', function (): void {
        it('has RoleView', fn (): Expectation => expect(PermissionEnum::RoleView->value)->toBe('role.view'));
        it('has RoleCreate', fn (): Expectation => expect(PermissionEnum::RoleCreate->value)->toBe('role.create'));
        it('has RoleEdit', fn (): Expectation => expect(PermissionEnum::RoleEdit->value)->toBe('role.edit'));
        it('has RoleDelete', fn (): Expectation => expect(PermissionEnum::RoleDelete->value)->toBe('role.delete'));
    });

    describe('permission permissions', function (): void {
        it('has PermissionView', fn (): Expectation => expect(PermissionEnum::PermissionView->value)->toBe('permission.view'));
        it('has PermissionCreate', fn (): Expectation => expect(PermissionEnum::PermissionCreate->value)->toBe('permission.create'));
        it('has PermissionEdit', fn (): Expectation => expect(PermissionEnum::PermissionEdit->value)->toBe('permission.edit'));
        it('has PermissionDelete', fn (): Expectation => expect(PermissionEnum::PermissionDelete->value)->toBe('permission.delete'));
    });

    describe('media permissions', function (): void {
        it('has MediaView', fn (): Expectation => expect(PermissionEnum::MediaView->value)->toBe('media.view'));
        it('has MediaCreate', fn (): Expectation => expect(PermissionEnum::MediaCreate->value)->toBe('media.create'));
        it('has MediaDelete', fn (): Expectation => expect(PermissionEnum::MediaDelete->value)->toBe('media.delete'));
    });

    describe('label', function (): void {
        it('returns the English label by default', function (): void {
            expect(PermissionEnum::UserView->label())->toBe('View users')
                ->and(PermissionEnum::RoleEdit->label())->toBe('Edit roles')
                ->and(PermissionEnum::PermissionDelete->label())->toBe('Delete permissions')
                ->and(PermissionEnum::MediaView->label())->toBe('View media')
                ->and(PermissionEnum::MediaCreate->label())->toBe('Upload media')
                ->and(PermissionEnum::MediaDelete->label())->toBe('Delete media');
        });

        it('returns the Indonesian label in the id locale', function (): void {
            app()->setLocale('id');

            expect(PermissionEnum::UserView->label())->toBe('Lihat pengguna')
                ->and(PermissionEnum::RoleEdit->label())->toBe('Ubah peran')
                ->and(PermissionEnum::PermissionDelete->label())->toBe('Hapus izin')
                ->and(PermissionEnum::MediaView->label())->toBe('Lihat media')
                ->and(PermissionEnum::MediaCreate->label())->toBe('Unggah media')
                ->and(PermissionEnum::MediaDelete->label())->toBe('Hapus media');
        });
    });

});
