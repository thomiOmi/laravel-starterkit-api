<?php

declare(strict_types=1);

namespace Modules\IAM\Http\Requests\V1;

use App\Enums\PermissionEnum;
use App\Http\Requests\PaginationRequest;

final class PermissionListRequest extends PaginationRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionEnum::PermissionView->value) ?? false;
    }
}
