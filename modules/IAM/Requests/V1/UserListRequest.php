<?php

declare(strict_types=1);

namespace Modules\IAM\Requests\V1;

use App\Http\Requests\PaginationRequest;

final class UserListRequest extends PaginationRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('user.view') ?? false;
    }
}
