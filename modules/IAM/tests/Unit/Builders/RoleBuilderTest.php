<?php

declare(strict_types=1);

use App\Builders\BaseQueryBuilder;
use Modules\IAM\Builders\RoleBuilder;

covers(RoleBuilder::class);

describe('RoleBuilder', function () {
    it('extends BaseQueryBuilder', function () {
        $parent = new ReflectionClass(RoleBuilder::class)->getParentClass();

        expect($parent)->toBeInstanceOf(ReflectionClass::class);

        if ($parent instanceof ReflectionClass) {
            expect($parent->getName())->toBe(BaseQueryBuilder::class);
        }
    });

    it('exposes expected configuration', function () {
        $reflection = new ReflectionClass(RoleBuilder::class);
        $instance = $reflection->newInstanceWithoutConstructor();

        expect($reflection->getProperty('allowedFilters')->getValue($instance))->toBe(['name'])
            ->and($reflection->getProperty('allowedSorts')->getValue($instance))
            ->toBe(['name', 'created_at'])
            ->and($reflection->getProperty('searchableColumns')->getValue($instance))
            ->toBe(['name', 'description'])
            ->and($reflection->getProperty('allowedIncludes')->getValue($instance))->toBe(['permissions']);
    });
});
