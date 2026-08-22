<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use App\Contracts\MediaUrlResolver;
use App\Enums\MediaCollection;
use InvalidArgumentException;
use Modules\IAM\Models\User;
use Modules\IAM\Payloads\V1\UpdateProfilePayload;

final readonly class UpdateProfileAction
{
    /**
     * @param  MediaUrlResolver|null  $mediaUrls  The cross-module media URL
     *                                            resolver, when the Media
     *                                            module is present. Null
     *                                            disables the avatar
     *                                            feature.
     */
    public function __construct(
        private ?MediaUrlResolver $mediaUrls = null,
    ) {}

    /**
     * @return array{user: User, verification_required: bool}
     */
    public function handle(User $user, UpdateProfilePayload $payload): array
    {
        $verificationRequired = false;

        if ($payload->name !== null) {
            $user->name = $payload->name;
        }

        if ($payload->email !== null && $payload->email !== $user->email) {
            $user->email = $payload->email;
            $user->email_verified_at = null;
            $verificationRequired = true;
        }

        if ($payload->avatarMediaId !== null) {
            $user->avatar = $this->resolveAvatarUrl($payload->avatarMediaId, $user);
        }

        $user->save();

        if ($verificationRequired) {
            $user->sendEmailVerificationNotification();
        }

        return [
            'user' => $user,
            'verification_required' => $verificationRequired,
        ];
    }

    private function resolveAvatarUrl(string $mediaId, User $user): string
    {
        if ($this->mediaUrls === null) {
            throw new InvalidArgumentException(__('validation.avatar_unavailable'));
        }

        return $this->mediaUrls->forOwner($mediaId, $user, MediaCollection::Avatars->value);
    }
}
