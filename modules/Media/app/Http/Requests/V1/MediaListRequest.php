<?php

declare(strict_types=1);

namespace Modules\Media\Http\Requests\V1;

use App\Enums\PermissionEnum;
use App\Http\Requests\PaginationRequest;

class MediaListRequest extends PaginationRequest
{
    #[\Override]
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionEnum::MediaView->value) ?? false;
    }
}
