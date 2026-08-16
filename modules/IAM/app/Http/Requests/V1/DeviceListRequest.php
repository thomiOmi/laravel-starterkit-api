<?php

declare(strict_types=1);

namespace Modules\IAM\Http\Requests\V1;

use App\Http\Requests\PaginationRequest;

final class DeviceListRequest extends PaginationRequest
{
    public function authorize(): bool
    {
        return true;
    }
}
