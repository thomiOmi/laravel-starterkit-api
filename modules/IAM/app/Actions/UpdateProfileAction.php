<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Modules\IAM\Models\User;
use Modules\IAM\Payloads\V1\UpdateProfilePayload;
use Modules\Media\Models\Media;

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

        if ($payload->avatarMediaId !== null) {
            $user->avatar = $this->resolveAvatarUrl($payload->avatarMediaId, $user);
        }

        if ($verificationRequired) {
            $user->save();
            $user->sendEmailVerificationNotification();
        } else {
            $user->save();
        }

        return [
            'user' => $user,
            'verification_required' => $verificationRequired,
        ];
    }

    private function resolveAvatarUrl(string $mediaId, User $user): string
    {
        $media = Media::query()->find($mediaId);

        if ($media === null || $media->disk !== 'public' || $media->uploaded_by !== $user->id) {
            throw new InvalidArgumentException(__('validation.avatar_invalid'));
        }

        /** @var FilesystemAdapter $storage */
        $storage = Storage::disk('public');

        return $storage->url($media->path);
    }
}
