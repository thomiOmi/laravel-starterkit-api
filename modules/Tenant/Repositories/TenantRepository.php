<?php

declare(strict_types=1);

namespace Modules\Tenant\Repositories;

use App\Repositories\BaseRepository;
use Modules\Tenant\Models\Tenant;

/**
 * @extends BaseRepository<Tenant>
 */
class TenantRepository extends BaseRepository
{
    public function __construct(Tenant $model)
    {
        parent::__construct($model);
    }

    public function model(): string
    {
        return Tenant::class;
    }
}
