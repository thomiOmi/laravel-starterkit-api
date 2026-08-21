<?php

declare(strict_types=1);

use App\Builders\BaseQueryBuilder;
use Modules\IAM\Builders\UserBuilder;

covers(UserBuilder::class);

describe('UserBuilder', function () {
    it('extends BaseQueryBuilder', function () {
        $parent = new ReflectionClass(UserBuilder::class)->getParentClass();

        expect($parent)->toBeInstanceOf(ReflectionClass::class);

        if ($parent instanceof ReflectionClass) {
            expect($parent->getName())->toBe(BaseQueryBuilder::class);
        }
    });

    it('exposes expected allowed filters', function () {
        $reflection = new ReflectionClass(UserBuilder::class);
        $property = $reflection->getProperty('allowedFilters');
        $instance = $reflection->newInstanceWithoutConstructor();
        $filters = $property->getValue($instance);

        expect($filters)->toContain('name', 'email', 'role', 'status', 'trashed')
            ->and($filters)->toContain('created_at');
    });

    it('exposes allowed sorts, fields and searchable columns', function () {
        $reflection = new ReflectionClass(UserBuilder::class);
        $instance = $reflection->newInstanceWithoutConstructor();

        $sorts = $reflection->getProperty('allowedSorts')->getValue($instance);
        $fields = $reflection->getProperty('allowedFields')->getValue($instance);
        $searchable = $reflection->getProperty('searchableColumns')->getValue($instance);
        $exact = $reflection->getProperty('exactMatchColumns')->getValue($instance);

        expect($sorts)->toContain('name', 'email', 'created_at')
            ->and($fields)->toContain('id', 'name', 'email', 'avatar')
            ->and($searchable)->toContain('name', 'email')
            ->and($exact)->toContain('status');
    });
});
