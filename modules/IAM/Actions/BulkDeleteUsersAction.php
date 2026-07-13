<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use App\Contracts\Identity;
use App\Enums\RoleEnum;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Cache;
use Modules\IAM\Models\User;

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

        /** @var Identity|null $currentUser */
        $currentUser = $this->auth->user();

        if ($currentUser === null || ! $currentUser->hasRole(RoleEnum::SuperAdmin->value)) {
            $ids = User::query()
                ->whereIn('id', $ids)
                ->whereDoesntHave('roles', fn ($q) => $q->where('name', RoleEnum::SuperAdmin->value))
                ->pluck('id')
                ->toArray();
        }

        if ($ids === []) {
            return 0;
        }

        /** @var array<int, string|int> $ids */
        foreach ($ids as $id) {
            Cache::forget("user_{$id}");
        }

        /** @var int $count */
        $count = User::whereIn('id', $ids)->delete();

        return $count;
    }
}
