<?php

namespace App\DTOs;

use Illuminate\Http\Request;

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
     */
    public function __construct(
        public int $page = 1,
        public int $per_page = 15,
        public ?string $search = null,
        public ?string $sort_by = null,
        public string $sort_direction = 'asc'
    ) {}

    /**
     * Create a DataTableDTO instance from a request.
     *
     * @param  Request  $request  The incoming HTTP request.
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            page: (int) $request->query('page', 1),
            per_page: (int) $request->query('per_page', 15),
            search: $request->query('search'),
            sort_by: $request->query('sort_by'),
            sort_direction: $request->query('sort_direction', 'asc')
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
        ];
    }
}
