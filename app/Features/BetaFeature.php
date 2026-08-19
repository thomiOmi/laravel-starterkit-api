<?php

declare(strict_types=1);

namespace App\Features;

use App\Contracts\Identity;
use App\Enums\RoleEnum;

final class BetaFeature
{
    /**
     * Resolve the feature's initial value.
     */
    public function resolve(Identity $user): bool
    {
        return $user->hasRole(RoleEnum::Admin->value);
    }
}
