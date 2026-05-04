<?php

declare(strict_types=1);

namespace Modules\User\Models;

use App\Notifications\VerifyEmail;
use App\Traits\Models\HasAuditLogs;
use App\Traits\Models\HasDefaultBehavior;
use App\Traits\Models\HasTenant;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;
use Modules\User\Database\Factories\UserFactory;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'avatar'])]
#[Hidden(['password', 'remember_token'])]
/**
 * @property string $id
 * @property string $name
 * @property string $email
 * @property string|null $avatar
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Role> $roles
 * @property-read Collection<int, Permission> $permissions
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasAuditLogs, HasDefaultBehavior, HasFactory, HasRoles, HasTenant, Notifiable;

    /**
     * The user's avatar URL or path.
     */
    public ?string $avatar = null;

    /**
     * Send the email verification notification.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmail);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
