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
        $record = DB::table('media')->where('id', $mediaId)->first();

        if ($record === null) {
            throw new InvalidArgumentException(__('validation.avatar_unavailable'));
        }

        if (! is_string($record->collection_name)
            || ! is_string($record->model_type)
            || (! is_string($record->model_id) && ! is_int($record->model_id))
            || ! is_string($record->disk)
            || ! is_string($record->visibility)
            || ! is_string($record->path)
        ) {
            throw new InvalidArgumentException(__('validation.avatar_unavailable'));
        }

        $modelId = (string) $record->model_id;
        $key = $user->getKey();

        if (! is_string($key) && ! is_int($key)) {
            throw new InvalidArgumentException(__('validation.avatar_unavailable'));
        }

        if (
            $record->collection_name !== 'avatars'
            || $record->model_type !== $user->getMorphClass()
            || $modelId !== (string) $key
            || $record->disk !== config()->string('media.disk', 'public')
            || $record->visibility !== 'public'
        ) {
            throw new InvalidArgumentException(__('validation.avatar_unavailable'));
        }

        return Storage::disk($record->disk)->url($record->path);
    }
}
