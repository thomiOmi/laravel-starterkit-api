<?php

declare(strict_types=1);

namespace Modules\Media\Http\Requests\V1;

use App\Http\Requests\PaginationRequest;

class MediaListRequest extends PaginationRequest
{
    #[\Override]
    public function authorize(): bool
    {
        // The listing is scoped to the caller's own media in the
        // controller, matching the owner-may-view policy rule.
        return $this->user() !== null;
    }
}
