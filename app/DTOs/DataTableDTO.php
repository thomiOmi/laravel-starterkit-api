<?php

declare(strict_types=1);

namespace App\DTOs;

use Illuminate\Http\Request;

/**
 * Data Transfer Object for DataTable parameters.
 */
readonly class DataTableDTO
{
    /**
     * Create a new DataTableDTO instance.
     *
     * @param  int  $page  The current page number.
     * @param  int  $per_page  The number of items per page.
     * @param  string|null  $search  The search query.
     * @param  string|null  $sort_by  The column to sort by.
     * @param  string  $sort_direction  The direction to sort (asc or desc).
     * @param  array<string, mixed>  $filters  Key-value pairs for column filtering.
     */
    public function __construct(
        public int $page = 1,
        public int $per_page = 10,
        public ?string $search = null,
        public ?string $sort_by = null,
        public string $sort_direction = 'asc',
        public array $filters = []
    ) {}

    /**
     * Create a DataTableDTO instance from a request.
     *
     * @param  Request  $request  The incoming HTTP request.
     */
    public static function fromRequest(Request $request): self
    {
        $filtersInput = $request->query('filters', []);
        /** @var array<string, mixed> $filters */
        $filters = is_array($filtersInput) ? $filtersInput : [];

        /** @var string $page */
        $page = $request->query('page', '1');
        /** @var string $perPage */
        $perPage = $request->query('per_page', '10');

        $search = $request->query('search');
        $sortBy = $request->query('sort_by');
        $sortDirection = $request->query('sort_direction', 'asc');

        return new self(
            page: (int) $page,
            per_page: (int) $perPage,
            search: is_string($search) ? $search : null,
            sort_by: is_string($sortBy) ? $sortBy : null,
            sort_direction: in_array(strtolower((string) $sortDirection), ['asc', 'desc'], true)
                ? strtolower((string) $sortDirection)
                : 'asc',
            filters: $filters
        );
    }

    /**
     * Convert the DTO to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'page' => $this->page,
            'per_page' => $this->per_page,
            'search' => $this->search,
            'sort_by' => $this->sort_by,
            'sort_direction' => $this->sort_direction,
            'filters' => $this->filters,
        ];
    }
}
