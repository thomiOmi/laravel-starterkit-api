<?php

declare(strict_types=1);

namespace Modules\Blog\DTOs;

use Illuminate\Foundation\Http\FormRequest;

readonly class BlogDTO
{
    public function __construct(
        public string $title,
        public string $content,
        public ?string $user_id = null,
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        return new self(
            title: $request->validated('title'),
            content: $request->validated('content'),
            user_id: $request->user()?->getKey(),
        );
    }
}
