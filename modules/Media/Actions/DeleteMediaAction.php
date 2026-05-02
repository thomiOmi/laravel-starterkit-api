<?php

declare(strict_types=1);

namespace Modules\Media\Actions;

use Modules\Media\Repositories\MediaRepository;

class DeleteMediaAction
{
    public function __construct(
        protected MediaRepository $repository
    ) {}

    /**
     * Delete media by ID.
     */
    public function execute(string $id): void
    {
        $media = $this->repository->findById($id);
        $media->delete();
    }
}
