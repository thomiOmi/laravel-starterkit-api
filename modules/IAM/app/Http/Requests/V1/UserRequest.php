<?php

declare(strict_types=1);

namespace Modules\IAM\Http\Requests\V1;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Contracts\Identity;
use App\Enums\PermissionEnum;
use App\Enums\UserStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rules\Unique;
use Modules\IAM\Payloads\V1\UserPayload;

/**
 * User Request
 *
 * The request parameters for creating or updating a user.
 */
final class UserRequest extends FormRequest
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Normalize the email before validation so the unique rule compares
     * case-insensitively across database drivers.
     */
    #[\Override]
    protected function prepareForValidation(): void
    {
        $this->normalizeEmail();
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        /** @var Identity $user */
        if ($this->isMethod('POST')) {
            /** @var class-string<Model> $userModel */
            $userModel = (string) config('auth.providers.users.model');

            return $user->can('create', $userModel);
        }

        /** @var Identity|Model|null $targetUser */
        $targetUser = $this->route('user');

        if (! $targetUser instanceof Model) {
            return $user->can(PermissionEnum::UserEdit->value);
        }

        return $user->can('update', $targetUser);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, ValidationRule|Password|Unique|Enum|string>> The validation rules.
     */
    public function rules(): array
    {
        $routeUser = $this->route('user');
        $userId = $routeUser instanceof Model ? $routeUser->getKey() : (is_string($routeUser) ? $routeUser : null);
        $userId = is_string($userId) || is_int($userId) ? (string) $userId : null;

        $statusRule = $this->user()?->can(PermissionEnum::UserEdit->value) === true
            ? [Rule::enum(UserStatusEnum::class)]
            : ['prohibited'];

        return [
            ...$this->profileRules($userId),
            'password' => $this->passwordRules($this->isMethod('POST')),
            'status' => $statusRule,
        ];
    }

    /**
     * Get the payload for the request.
     */
    public function payload(): UserPayload
    {
        return UserPayload::fromRequest($this);
    }
}
