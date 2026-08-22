<?php

declare(strict_types=1);

use App\Builders\BaseQueryBuilder;
use Modules\Media\Builders\MediaBuilder;

covers(MediaBuilder::class);

describe('MediaBuilder', function () {
    it('extends BaseQueryBuilder', function () {
        $parent = new ReflectionClass(MediaBuilder::class)->getParentClass();

        expect($parent)->toBeInstanceOf(ReflectionClass::class);

        if ($parent instanceof ReflectionClass) {
            expect($parent->getName())->toBe(BaseQueryBuilder::class);
        }
    });

    it('exposes expected allowed filters', function () {
        $reflection = new ReflectionClass(MediaBuilder::class);
        $instance = $reflection->newInstanceWithoutConstructor();
        $filters = $reflection->getProperty('allowedFilters')->getValue($instance);

        expect($filters)->toContain('collection_name');
    });

    it('exposes allowed sorts and exact columns', function () {
        $reflection = new ReflectionClass(MediaBuilder::class);
        $instance = $reflection->newInstanceWithoutConstructor();

        $sorts = $reflection->getProperty('allowedSorts')->getValue($instance);
        $exact = $reflection->getProperty('exactMatchColumns')->getValue($instance);

        expect($sorts)->toContain('created_at', 'size')
            ->and($exact)->toContain('collection_name');
    });
});
