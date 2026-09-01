<?php

declare(strict_types=1);

namespace Modules\IAM\Models;

use App\Concerns\HasDefaultBehavior;
use App\Contracts\Identity;
use App\Enums\UserStatusEnum;
use App\Notifications\ResetPassword;
use App\Notifications\VerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;
use Modules\IAM\Builders\UserBuilder;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Observers\UserObserver;
use Modules\IAM\Policies\UserPolicy;
use Modules\Media\Contracts\HasMedia;
use Modules\Media\Models\Media;
use Modules\Media\Traits\InteractsWithMedia;
use SensitiveParameter;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property string $id The unique identifier for the user.
 * @property string $name The name of the user.
 * @property string $email The email address of the user.
 * @property UserStatusEnum $status The account status of the user.
 * @property string|null $password The hashed password of the user.
 * @property string|null $remember_token The remember token for the user.
 * @property string|null $avatar The avatar URL of the user.
 * @property Carbon|null $email_verified_at The timestamp when the email was verified.
 * @property Carbon|null $created_at The timestamp when the user was created.
 * @property Carbon|null $updated_at The timestamp when the user was last updated.
 * @property Carbon|null $deleted_at The timestamp when the user was soft deleted.
 * @property-read Collection<int, SocialAccount> $socialAccounts The social accounts linked to the user.
 * @property-read Collection<int, Role> $roles The roles assigned to the user.
 * @property-read Collection<int, Permission> $permissions The permissions assigned to the user.
 */
#[Fillable(['name', 'email', 'status', 'password', 'avatar'])]
#[Hidden(['password', 'remember_token'])]
#[UseFactory(UserFactory::class)]
#[UseEloquentBuilder(UserBuilder::class)]
#[UsePolicy(UserPolicy::class)]
#[ObservedBy([UserObserver::class])]
class User extends Authenticatable implements HasMedia, Identity
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasDefaultBehavior, HasFactory, HasRoles, InteractsWithMedia, Notifiable, SoftDeletes;

    /**
     * Get the social accounts linked to this user.
     *
     * @return HasMany<SocialAccount, $this>
     */
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    /**
     * Determine whether the user has a password set.
     */
    public function hasPassword(): bool
    {
        return $this->password !== null;
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
    public function sendPasswordResetNotification(#[SensitiveParameter] $token): void
    {
        $this->notify(new ResetPassword($token));
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatars')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/bmp']);

        $this->addMediaCollection('default');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumbnail')
            ->width(320)
            ->height(320)
            ->fit('cover')
            ->format('webp')
            ->quality(80)
            ->performOnCollections('avatars');

        $this->addMediaConversion('medium')
            ->width(1024)
            ->format('webp')
            ->quality(85)
            ->performOnCollections(['avatars', 'default']);
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
            'status' => UserStatusEnum::class,
            'password' => 'hashed',
        ];
    }
}
