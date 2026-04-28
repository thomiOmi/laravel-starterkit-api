<?php

declare(strict_types=1);

namespace Modules\ApiKey\Guards;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Modules\ApiKey\Models\ApiKey;

class ApiKeyGuard implements Guard
{
    /**
     * The authenticatable user.
     */
    protected ?Authenticatable $user = null;

    /**
     * ApiKeyGuard constructor.
     */
    public function __construct(
        protected Request $request
    ) {}

    /**
     * Determine if the current user is authenticated.
     */
    public function check(): bool
    {
        return ! is_null($this->user());
    }

    /**
     * Determine if the current user is a guest.
     */
    public function guest(): bool
    {
        return ! $this->check();
    }

    /**
     * Get the currently authenticated user.
     */
    public function user(): ?Authenticatable
    {
        if ($this->user) {
            return $this->user;
        }

        $key = $this->request->header('X-API-Key');
        if (! $key) {
            return null;
        }

        $query = ApiKey::where('key', hash('sha256', $key))
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });

        if (tenancy()->initialized) {
            $query->where('tenant_id', tenant('id'));
        }

        $apiKey = $query->first();

        if (! $apiKey) {
            return null;
        }

        // IP Whitelist Check
        if ($apiKey->ip_whitelist && ! in_array($this->request->ip(), $apiKey->ip_whitelist)) {
            return null;
        }

        $apiKey->update(['last_used_at' => now()]);

        return $this->user = $apiKey->user;
    }

    /**
     * Get the ID for the currently authenticated user.
     */
    public function id(): string|int|null
    {
        return $this->user()?->getAuthIdentifier();
    }

    /**
     * Validate a user's credentials.
     *
     * @param  array<string, mixed>  $credentials
     */
    public function validate(array $credentials = []): bool
    {
        return false;
    }

    /**
     * Set the current user.
     */
    public function setUser(Authenticatable $user): void
    {
        $this->user = $user;
    }

    /**
     * Determine if the guard has a user instance.
     */
    public function hasUser(): bool
    {
        return ! is_null($this->user);
    }
}
