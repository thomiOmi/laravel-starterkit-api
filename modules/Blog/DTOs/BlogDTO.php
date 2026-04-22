<?php

declare(strict_types=1);

namespace Modules\Blog\DTOs;

use Illuminate\Http\Request;

class BlogDTO
{
    public function __construct(
        public string $title,
        public string $content,
        public ?string $user_id = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            title: $request->validated('title'),
            content: $request->validated('content'),
            user_id: $request->user()?->id,
        );
    }
}
