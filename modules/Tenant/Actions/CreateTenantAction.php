<?php

declare(strict_types=1);

namespace Modules\Tenant\Actions;

use Modules\Tenant\DTOs\TenantDTO;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Repositories\TenantRepository;

class CreateTenantAction
{
    public function __construct(
        protected TenantRepository $repository
    ) {}

    /**
     * Execute the create tenant action.
     */
    public function execute(TenantDTO $dto): Tenant
    {
        $tenant = $this->repository->create(['id' => $dto->id]);
        $tenant->createDomain(['domain' => $dto->domain]);

        return $tenant;
    }
}
