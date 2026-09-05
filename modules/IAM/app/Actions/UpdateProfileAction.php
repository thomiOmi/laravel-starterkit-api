<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use InvalidArgumentException;
use Modules\IAM\Models\User;
use Modules\IAM\Payloads\V1\UpdateProfilePayload;

final readonly class UpdateProfileAction
{
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

        if ($payload->avatarFile !== null) {
            // Profile avatars deliberately bypass the media.create permission:
            // owner-only, locked to the avatars collection, guarded by the
            // collection mime rules in UploadMediaAction. Single-file
            // replacement removes the previous avatar file.
            $media = $user->addMedia($payload->avatarFile)->toMediaCollection('avatars');

            $user->avatar = $media->url() ?? throw new InvalidArgumentException(__('validation.avatar_unavailable'));
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
}
