<?php

declare(strict_types=1);

namespace Modules\IAM\Requests\V1;

use App\Enums\PermissionEnum;
use App\Http\Requests\PaginationRequest;

final class RoleListRequest extends PaginationRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionEnum::RoleView->value) ?? false;
    }
}
