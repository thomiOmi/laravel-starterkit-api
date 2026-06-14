<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use Illuminate\Contracts\Auth\Guard;
use Modules\User\Models\User;

final readonly class BulkDeleteUsersAction
{
    public function __construct(
        private Guard $auth
    ) {}

    /**
     * @param  array<int, string|int>  $ids
     */
    public function handle(array $ids): int
    {
        $ids = array_filter($ids, fn (string|int $id): bool => $id !== $this->auth->id());

        if ($ids === []) {
            return 0;
        }

        /** @var int $count */
        $count = User::whereIn('id', $ids)->delete();

        return $count;
    }
}
