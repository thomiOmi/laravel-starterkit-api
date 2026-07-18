<?php

declare(strict_types=1);

namespace App\Http\Filters;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

final readonly class BasePaginate
{
    private const int DEFAULT_MAX_PER_PAGE = 100;

    public function __construct(
        private Request $request,
        private int $maxPerPage = self::DEFAULT_MAX_PER_PAGE,
    ) {}

    /**
     * Pipe-able pagination with a DDoS-guard per-page cap.
     *
     * Apply via `->pipe(new BasePaginate(request()))` at the end of a Builder chain,
     * after filtering with `BaseFilter`.
     *
     * Reads `page[size]` (default 15) and `page[number]` (default 1) from the request.
     * The `$maxPerPage` constructor parameter caps the page size regardless of what
     * the client sends — the API will never return more rows than this value.
     *
     * @example ?page[size]=25&page[number]=2 → per_page=25, page=2
     * @example ?page[size]=500               → per_page=100 (capped to DEFAULT_MAX_PER_PAGE)
     * @example ?page[size]=-1                → per_page=1 (clamped to minimum)
     *
     * @see BaseFilter
     * @see PaginationRequest
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return LengthAwarePaginator<int, TModel>
     */
    public function __invoke(Builder $query): LengthAwarePaginator
    {
        $perPage = max(1, min(
            (int) $this->request->integer('page.size', 15),
            $this->maxPerPage,
        ));

        return $query->paginate(
            perPage: $perPage,
            page: max(1, (int) $this->request->integer('page.number', 1)),
        );
    }
}
