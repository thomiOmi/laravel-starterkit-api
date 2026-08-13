<?php

declare(strict_types=1);

namespace Modules\IAM\Models;

use App\Concerns\HasDefaultBehavior;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use Modules\IAM\Builders\RoleBuilder;
use Modules\IAM\Database\Factories\RoleFactory;
use Modules\IAM\Policies\RolePolicy;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * @property string $id
 * @property string $name
 * @property string|null $description
 * @property string $guard_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Permission> $permissions
 */
#[Fillable([
    'name',
    'guard_name',
    'description',
])]
#[Hidden(['guard_name'])]
#[UseFactory(RoleFactory::class)]
#[UseEloquentBuilder(RoleBuilder::class)]
#[UsePolicy(RolePolicy::class)]
class Role extends SpatieRole
{
    /** @use HasFactory<RoleFactory> */
    use HasDefaultBehavior, HasFactory;
}
