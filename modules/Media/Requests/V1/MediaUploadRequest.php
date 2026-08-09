<?php

declare(strict_types=1);

namespace Modules\Media\Requests\V1;

use App\Enums\MediaVisibilityEnum;
use App\Enums\PermissionEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Modules\Media\Payloads\V1\MediaUploadPayload;

/**
 * Upload Media Request
 */
final class MediaUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionEnum::MediaCreate->value) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, ValidationRule|Enum|string>> The validation rules.
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:10240'],
            'visibility' => ['sometimes', Rule::enum(MediaVisibilityEnum::class)],
        ];
    }

    /**
     * Get the request payload.
     */
    public function payload(): MediaUploadPayload
    {
        return MediaUploadPayload::fromRequest($this);
    }
}
