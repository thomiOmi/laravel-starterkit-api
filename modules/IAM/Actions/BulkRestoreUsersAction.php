<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use App\Enums\RoleEnum;
use Illuminate\Contracts\Auth\Guard;
use Modules\IAM\Models\User;

final readonly class BulkRestoreUsersAction
{
    public function __construct(
        private Guard $auth
    ) {}

    /**
     * @param  array<int, string|int>  $ids
     */
    public function handle(array $ids): int
    {
        /** @var User|null $currentUser */
        $currentUser = $this->auth->user();

        if ($currentUser !== null && ! $currentUser->hasRole(RoleEnum::SuperAdmin->value)) {
            $ids = User::query()
                ->onlyTrashed()
                ->whereIn('id', $ids)
                ->whereDoesntHave('roles', fn ($q) => $q->where('name', RoleEnum::SuperAdmin->value))
                ->pluck('id')
                ->toArray();
        }

        if ($ids === []) {
            return 0;
        }

        /** @var int $count */
        $count = User::onlyTrashed()->whereIn('id', $ids)->restore();

        return $count;
    }
}
