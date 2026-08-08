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

    describe('allowsAuthentication', function (): void {
        it('allows active accounts', fn () => expect(UserStatusEnum::Active->allowsAuthentication())->toBeTrue());
        it('allows pending accounts awaiting email verification', fn () => expect(UserStatusEnum::Pending->allowsAuthentication())->toBeTrue());
        it('blocks inactive accounts', fn () => expect(UserStatusEnum::Inactive->allowsAuthentication())->toBeFalse());
        it('blocks suspended accounts', fn () => expect(UserStatusEnum::Suspended->allowsAuthentication())->toBeFalse());
        it('blocks banned accounts', fn () => expect(UserStatusEnum::Banned->allowsAuthentication())->toBeFalse());
    });

    describe('blockedMessageKey', function (): void {
        it('returns the banned message key', fn () => expect(UserStatusEnum::Banned->blockedMessageKey())->toBe('auth.account_banned'));
        it('returns the suspended message key', fn () => expect(UserStatusEnum::Suspended->blockedMessageKey())->toBe('auth.account_suspended'));
        it('returns the inactive message key', fn () => expect(UserStatusEnum::Inactive->blockedMessageKey())->toBe('auth.account_inactive'));
        it('falls back to the generic failed key for allowed statuses', fn () => expect(UserStatusEnum::Active->blockedMessageKey())->toBe('auth.failed'));
    });

    describe('label', function (): void {
        it('returns the English label by default', function () {
            expect(UserStatusEnum::Active->label())->toBe('Active')
                ->and(UserStatusEnum::Inactive->label())->toBe('Inactive')
                ->and(UserStatusEnum::Pending->label())->toBe('Pending')
                ->and(UserStatusEnum::Suspended->label())->toBe('Suspended')
                ->and(UserStatusEnum::Banned->label())->toBe('Banned');
        });

        it('returns the Indonesian label in the id locale', function () {
            app()->setLocale('id');

            expect(UserStatusEnum::Active->label())->toBe('Aktif')
                ->and(UserStatusEnum::Inactive->label())->toBe('Nonaktif')
                ->and(UserStatusEnum::Pending->label())->toBe('Menunggu')
                ->and(UserStatusEnum::Suspended->label())->toBe('Ditangguhkan')
                ->and(UserStatusEnum::Banned->label())->toBe('Diblokir');
        });
    });

});
