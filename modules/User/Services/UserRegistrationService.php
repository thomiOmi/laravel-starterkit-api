<?php

declare(strict_types=1);

namespace Modules\User\Services;

use App\Services\BaseService;
use Modules\User\Actions\CreateUserAction;
use Modules\User\DTOs\UserDTO;
use Modules\User\Models\User;

class UserRegistrationService extends BaseService
{
    public function __construct(
        protected CreateUserAction $createUserAction
    ) {}

    /**
     * Register a new user with all necessary steps.
     *
     * @param  UserDTO  $dto  The user data transfer object.
     * @return User The newly registered user.
     */
    public function register(UserDTO $dto): User
    {
        /** @var User */
        return $this->transactional(function () use ($dto) {
            // 1. Create the user using the existing Action
            $user = $this->createUserAction->execute($dto);

            // 2. We could perform more actions here...
            // e.g. $this->createProfile->execute($user, $dto->profileData);
            // e.g. $this->sendWelcomeEmail->execute($user);

            return $user;
        });
    }
}
