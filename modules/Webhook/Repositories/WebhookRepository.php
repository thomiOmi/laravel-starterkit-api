<?php

declare(strict_types=1);

namespace Modules\Webhook\Repositories;

use App\Repositories\BaseRepository;
use Modules\Webhook\Models\Webhook;

/**
 * @extends BaseRepository<Webhook>
 */
class WebhookRepository extends BaseRepository
{
    public function __construct(Webhook $model)
    {
        parent::__construct($model);
    }

    public function model(): string
    {
        return Webhook::class;
    }
}
