<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

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
        // Models are the public seam between modules: avatar resolution
        // consumes Media::getPath() instead of rebuilding the storage
        // path manually, so prefixes and custom generators apply here too.
        $media = Media::query()->whereKey($mediaId)->first();

        if ($media === null || ! $media->belongsToModel($user)) {
            throw new InvalidArgumentException(__('validation.avatar_unavailable'));
        }

        if (
            $media->collection_name !== 'avatars'
            || ! $media->isPublic()
            || $media->disk !== config()->string('media.disk', 'public')
        ) {
            throw new InvalidArgumentException(__('validation.avatar_unavailable'));
        }

        $path = $media->getPath();

        if (! is_string($path)) {
            throw new InvalidArgumentException(__('validation.avatar_unavailable'));
        }

        return Storage::disk($media->disk)->url($path);
    }
}
