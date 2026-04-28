<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Auth\Events\Registered;
use Modules\User\DTOs\UserDTO;
use Modules\User\Models\User;
use Modules\User\Repositories\UserRepository;
use Modules\Webhook\Services\WebhookService;

class RegisterAction
{
    /**
     * Create a new RegisterAction instance.
     */
    public function __construct(
        protected UserRepository $userRepository,
        protected WebhookService $webhookService
    ) {}

    /**
     * Execute the register action.
     *
     * @param  UserDTO  $dto  The user data transfer object.
     * @return array{user: User, access_token: string, token_type: string}
     */
    public function execute(UserDTO $dto): array
    {
        $user = $this->userRepository->create([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => $dto->password,
        ]);

        event(new Registered($user));

        $this->webhookService->dispatch('user.registered', [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => $user->created_at,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ];
    }
}
