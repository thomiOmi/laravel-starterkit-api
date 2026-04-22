<?php

declare(strict_types=1);

namespace Modules\Blog\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'author' => $this->user?->name,
            'created_at' => $this->created_at,
        ];
    }
}
