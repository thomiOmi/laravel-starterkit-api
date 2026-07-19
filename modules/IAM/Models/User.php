<?php

declare(strict_types=1);

namespace Modules\IAM\Models;

use App\Concerns\HasDefaultBehavior;
use App\Contracts\Identity;
use App\Enums\UserStatus;
use App\Notifications\ResetPassword;
use App\Notifications\VerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;
use Modules\IAM\Database\Factories\UserFactory;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property string $id The unique identifier for the user.
 * @property string $name The name of the user.
 * @property string $email The email address of the user.
 * @property UserStatus $status The account status of the user.
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
#[Fillable(['name', 'email', 'status', 'password', 'provider', 'provider_id', 'avatar'])]
#[Hidden(['password', 'remember_token', 'provider_id'])]
#[UseFactory(UserFactory::class)]
class User extends Authenticatable implements Identity
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasDefaultBehavior, HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saved(function (self $user) {
            if ($user->wasChanged('email_verified_at') && $user->email_verified_at !== null) {
                $user->updateQuietly(['status' => UserStatus::Active]);
            }
        });
    }

    /**
     * Send the email verification notification.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmail);
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token  The password reset token.
     */
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new ResetPassword($token));
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
            'status' => UserStatus::class,
            'password' => 'hashed',
            'provider_id' => 'encrypted',
        ];
    }
}
