<?php

declare(strict_types=1);

use App\Models\Sanctum\PersonalAccessToken;

describe('PersonalAccessToken', function () {

    describe('fillable', function () {
        it('includes ip_address', function () {
            expect((new PersonalAccessToken)->getFillable())->toContain('ip_address', 'user_agent');
        });
    });

    describe('casts', function () {
        it('casts abilities to json', function () {
            expect((new PersonalAccessToken)->getCasts()['abilities'])->toBe('json');
        });

        it('casts last_used_at to datetime', function () {
            expect((new PersonalAccessToken)->getCasts()['last_used_at'])->toBe('datetime');
        });

        it('casts expires_at to datetime', function () {
            expect((new PersonalAccessToken)->getCasts()['expires_at'])->toBe('datetime');
        });
    });

});
