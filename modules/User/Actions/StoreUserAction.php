<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Modules\User\Events\UserCreated;
use Modules\User\Models\User;
use Modules\User\Payloads\V1\UserPayload;
use Modules\User\Repositories\UserRepository;

/**
 * Action for creating a new user.
 */
final readonly class StoreUserAction
{
    /**
     * Create a new StoreUserAction instance.
     */
    public function __construct(
        private DatabaseManager $database,
        private UserRepository $repository
    ) {}

    /**
     * Execute the create user action.
     *
     * @param  UserPayload  $payload  The user payload.
     * @return User The newly created user instance.
     */
    public function handle(UserPayload $payload): User
    {
        $user = $this->database->transaction(function () use ($payload) {
            return $this->repository->create($payload->toArray());
        });

        defer(function () use ($user) {
            Log::info('User created', [
                'user_id' => $user->id,
                'trace_id' => Context::get('trace_id'),
            ]);

            UserCreated::dispatch($user);
        });

        return $user;
    }
}
