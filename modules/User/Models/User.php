<?php

declare(strict_types=1);

namespace Modules\User\Models;

use App\Notifications\ResetPassword;
use App\Notifications\VerifyEmail;
use App\Traits\Models\HasDefaultBehavior;
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

/**
 * @property string $id The unique identifier for the user.
 * @property string $name The name of the user.
 * @property string $email The email address of the user.
 * @property string|null $password The hashed password of the user.
 * @property string|null $remember_token The remember token for the user.
 * @property string|null $provider The social auth provider.
 * @property string|null $provider_id The social auth provider ID.
 * @property string|null $avatar The avatar URL of the user.
 * @property Carbon|null $email_verified_at The timestamp when the email was verified.
 * @property Carbon|null $created_at The timestamp when the user was created.
 * @property Carbon|null $updated_at The timestamp when the user was last updated.
 * @property Carbon|null $deleted_at The timestamp when the user was soft deleted.
 * @property-read Collection<int, Role> $roles The roles assigned to the user.
 * @property-read Collection<int, Permission> $permissions The permissions assigned to the user.
 */
#[Fillable(['name', 'email', 'password', 'provider', 'provider_id', 'avatar'])]
#[Hidden(['password', 'remember_token'])]
/**
 * @implements MustVerifyEmail
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasDefaultBehavior, HasFactory, HasRoles, Notifiable;

    /**
     * Send the email verification notification.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmail);
    }

    /**
     * Send the password reset notification.
     */
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        /** @var string $token */
        $this->notify(new ResetPassword($token));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string> An array of attribute names and their corresponding cast types.
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
     *
     * @return UserFactory The user factory instance.
     */
    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
