<?php

declare(strict_types=1);

namespace Modules\Blog\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;
use Modules\Blog\Models\Blog;

/**
 * @mixin Blog
 */
class BlogResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'author' => $this->whenLoaded('user', fn () => $this->user->name),
            'created_at' => $this->formatDate($this->created_at),
            'updated_at' => $this->formatDate($this->updated_at),
        ];
    }
}
