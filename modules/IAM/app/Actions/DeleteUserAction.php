<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Illuminate\Support\Facades\DB;
use Modules\IAM\Models\User;

final readonly class DeleteUserAction
{
    public function handle(User $user): bool
    {
        return DB::transaction(function () use ($user): bool {
            $user->tokens()->delete();

            return $user->delete() ?? false;
        });
    }
}
