<?php

declare(strict_types=1);

namespace Modules\Role\Tests\Unit;

use Modules\Role\Database\Factories\PermissionFactory;
use Modules\Role\Filters\PermissionFilter;
use Modules\Role\Repositories\PermissionRepository;

/**
 * Unit test for PermissionRepository.
 */
describe('PermissionRepository', function () {
    it('paginates all permissions', function () {
        PermissionFactory::new()->count(3)->create();
        $repo = app(PermissionRepository::class);

        $result = $repo->paginate(app(PermissionFilter::class));

        expect($result->total())->toBeGreaterThanOrEqual(3);
    });
});
