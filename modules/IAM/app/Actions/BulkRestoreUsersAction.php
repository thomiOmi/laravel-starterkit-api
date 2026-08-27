<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use App\Enums\RoleEnum;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Eloquent\Builder;
use Modules\IAM\Models\User;
use Spatie\Permission\Models\Role;

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
        if ($ids === []) {
            return 0;
        }

        /** @var User|null $currentUser */
        $currentUser = $this->auth->user();

        $query = User::onlyTrashed()->whereIn('id', $ids);

        if ($currentUser !== null && ! $currentUser->hasRole(RoleEnum::SuperAdmin->value)) {
            $query->whereDoesntHave('roles', function (Builder $q): void {
                /** @var Builder<Role> $q */
                $q->where('name', RoleEnum::SuperAdmin->value);
            });
        }

        // Apply filtering directly on the restore query builder to avoid double database round-trips via pluck().
        /** @var int $count */
        $count = $query->restore();

        return $count;
    }
}
