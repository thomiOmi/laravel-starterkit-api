<?php

declare(strict_types=1);

namespace Modules\ApiKey\Repositories;

use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\ApiKey\Models\ApiKey;

/**
 * @extends BaseRepository<ApiKey>
 */
class ApiKeyRepository extends BaseRepository
{
    /**
     * ApiKeyRepository constructor.
     */
    public function __construct(ApiKey $model)
    {
        parent::__construct($model);
    }

    /**
     * Get the model class name.
     */
    public function model(): string
    {
        return ApiKey::class;
    }

    /**
     * Find API Keys by user ID.
     *
     * @return Collection<int, ApiKey>
     */
    public function findByUserId(string $userId): Collection
    {
        return $this->model->newQuery()->where('user_id', $userId)->get();
    }
}
