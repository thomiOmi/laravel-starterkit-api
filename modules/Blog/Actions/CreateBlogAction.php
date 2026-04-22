<?php

declare(strict_types=1);

namespace Modules\Blog\Actions;

use Modules\Blog\DTOs\BlogDTO;
use Modules\Blog\Models\Blog;
use Modules\Blog\Repositories\BlogRepository;

class CreateBlogAction
{
    public function __construct(
        protected BlogRepository $repository
    ) {}

    /**
     * Execute the create blog action.
     *
     * @param  BlogDTO  $dto  The blog data transfer object.
     */
    public function execute(BlogDTO $dto): Blog
    {
        return $this->repository->create([
            'title' => $dto->title,
            'content' => $dto->content,
            'user_id' => $dto->user_id,
        ]);
    }
}
