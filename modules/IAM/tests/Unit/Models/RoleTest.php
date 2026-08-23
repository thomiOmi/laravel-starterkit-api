<?php

declare(strict_types=1);

use Modules\IAM\Models\Role;

covers(Role::class);

describe('Role', function () {
    it('has expected fillable attributes', function () {
        expect((new Role)->getFillable())->toContain('name', 'guard_name', 'description');
    });

    it('hides guard_name from serialization', function () {
        expect((new Role)->getHidden())->toContain('guard_name');
    });

    it('does not use auto-incrementing ids', function () {
        expect((new Role)->getIncrementing())->toBeFalse();
    });
});
