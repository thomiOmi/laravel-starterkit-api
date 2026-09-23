<?php

declare(strict_types=1);

use Modules\IAM\Models\Permission;

covers(Permission::class);

describe('Permission', function (): void {
    it('has expected fillable attributes', function (): void {
        expect((new Permission)->getFillable())->toContain('name', 'guard_name', 'description');
    });

    it('does not use auto-incrementing ids', function (): void {
        expect((new Permission)->getIncrementing())->toBeFalse();
    });
});
