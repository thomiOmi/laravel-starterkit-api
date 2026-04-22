<?php

declare(strict_types=1);

namespace Modules\Blog\Repositories;

use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Blog\Models\Blog;

/**
 * @extends BaseRepository<Blog>
 */
class BlogRepository extends BaseRepository
{
    public function __construct(Blog $model)
    {
        parent::__construct($model);
    }

    /**
     * Apply a search query to the database query for blogs.
     *
     * @param  Builder  $query  The query builder.
     * @param  string  $search  The search query.
     */
    protected function applySearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
                ->orWhere('content', 'like', "%{$search}%");
        });
    }

    /**
     * Get the columns that can be filtered for blogs.
     */
    protected function getFilterableColumns(): array
    {
        return ['title', 'content', 'user_id'];
    }

    /**
     * Get the latest blog titles for a simplified listing.
     *
     * @param  int  $limit  The maximum number of records.
     * @return Collection<int, Blog>
     */
    public function getLatestTitles(int $limit = 50): Collection
    {
        /** @var Collection<int, Blog> $collection */
        $collection = $this->model->newQuery()
            ->latest()
            ->limit($limit)
            ->get(['id', 'title']);

        return $collection;
    }

    /**
     * Get the columns that can be sorted for blogs.
     */
    protected function getSortableColumns(): array
    {
        return ['title', 'created_at'];
    }
}
