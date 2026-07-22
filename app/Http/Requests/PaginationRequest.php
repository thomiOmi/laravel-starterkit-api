<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaginationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'array'],
            'page.size' => [
                'sometimes',
                'integer',
                'between:'.$this->minPerPage().','.$this->maxPerPage(),
            ],
            'page.number' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function getPerPage(?int $default = null): int
    {
        return $this->integer('page.size', $default ?? $this->defaultPerPage());
    }

    public function getPage(?int $default = null): int
    {
        return $this->integer('page.number', $default ?? 1);
    }

    protected function defaultPerPage(): int
    {
        return config()->integer('pagination.default_per_page', 10);
    }

    protected function minPerPage(): int
    {
        return config()->integer('pagination.min_per_page', 1);
    }

    protected function maxPerPage(): int
    {
        return config()->integer('pagination.max_per_page', 100);
    }
}
