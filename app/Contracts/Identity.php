<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;

interface Identity extends Authenticatable, Authorizable, CanResetPassword, MustVerifyEmail
{
    /**
     * @param  string|array<int, string>  $roles
     */
    public function hasRole(string|array $roles, ?string $guard = null): bool;
}
