<?php

declare(strict_types=1);

use Modules\IAM\Models\Permission;

covers(Permission::class);

describe('Permission', function () {
    it('has expected fillable attributes', function () {
        expect((new Permission)->getFillable())->toContain('name', 'guard_name', 'description');
    });

    it('does not use auto-incrementing ids', function () {
        expect((new Permission)->getIncrementing())->toBeFalse();
    });
});
