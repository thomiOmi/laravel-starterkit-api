<?php

declare(strict_types=1);

namespace Modules\Media\Requests\V1;

use App\Http\Requests\PaginationRequest;

/**
 * List Media Request
 *
 * Handles pagination and sorting parameters for listing media.
 */
final class ListMediaRequest extends PaginationRequest {}
