<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Throwable;

abstract class BaseService
{
    /**
     * Execute a callback within a database transaction.
     *
     *
     * @throws Throwable
     */
    protected function transactional(callable $callback): mixed
    {
        return DB::transaction($callback);
    }
}
