<?php

declare(strict_types=1);

namespace Modules\Media\Enums;

enum MediaProcessingStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Processed = 'processed';
    case Failed = 'failed';
}
