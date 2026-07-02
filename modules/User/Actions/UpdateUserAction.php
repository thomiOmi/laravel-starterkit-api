<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use Illuminate\Support\Facades\Cache;
use Modules\IAM\Models\User;
use Modules\User\Payloads\V1\UserPayload;
use Modules\User\Repositories\UserRepository;

final readonly class UpdateUserAction
{
    public function __construct(
        private UserRepository $repository
    ) {}

    public function handle(string $id, UserPayload $payload): ?User
    {
        $user = $this->repository->findById($id);

        if (! $user) {
            return null;
        }

        $user->update($payload->toArray());

        Cache::forget("user_{$user->id}");

        return $user;
    }
}
