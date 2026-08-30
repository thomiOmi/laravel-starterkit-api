<?php

declare(strict_types=1);

namespace Modules\IAM\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Modules\IAM\Models\User;
use Modules\IAM\Payloads\V1\UpdateProfilePayload;

final readonly class UpdateProfileAction
{
    public function __construct() {}

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
        $media = DB::table('media')->where('id', $mediaId)->first();

        if (
            $media === null
            || ! is_string($media->uploaded_by)
            || ! is_string($media->collection_name)
            || ! is_string($media->disk)
            || ! is_string($media->path)
            || $media->uploaded_by !== $user->id
            || $media->collection_name !== 'avatars'
            || $media->disk !== config()->string('media.disk', 'public')
        ) {
            throw new InvalidArgumentException(__('validation.avatar_unavailable'));
        }

        return Storage::disk($media->disk)->url($media->path);
    }
}
