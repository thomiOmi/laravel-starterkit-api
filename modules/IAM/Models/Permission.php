<?php

declare(strict_types=1);

namespace Modules\IAM\Models;

use App\Concerns\HasDefaultBehavior;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use Modules\IAM\Database\Factories\PermissionFactory;
use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * @property string $id
 * @property string $name
 * @property string $guard_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Permission firstOrCreate(array<string, mixed> $attributes = [], array<string, mixed> $values = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static> where(string|\Closure $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 */
#[Fillable([
    'name',
    'guard_name',
])]
class Permission extends SpatiePermission
{
    /** @use HasFactory<PermissionFactory> */
    use HasDefaultBehavior, HasFactory;
}
