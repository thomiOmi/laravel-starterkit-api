<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Throwable;

abstract class BaseService
{
    /**
     * Execute a callback within a database transaction.
     *
     * @template T
     *
     * @param  \Closure(Connection): T  $callback
     * @return T
     *
     * @throws Throwable
     */
    protected function transactional(\Closure $callback): mixed
    {
        return DB::transaction($callback);
    }
}
