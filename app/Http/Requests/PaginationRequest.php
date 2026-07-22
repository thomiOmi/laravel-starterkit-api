<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaginationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
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

    /**
     * Get the validated per-page value, falling back to the given default or the configured default.
     */
    public function getPerPage(?int $default = null): int
    {
        return $this->integer('page.size', $default ?? $this->defaultPerPage());
    }

    /**
     * Get the validated page number, falling back to the given default or 1.
     */
    public function getPage(?int $default = null): int
    {
        return $this->integer('page.number', $default ?? 1);
    }

    /**
     * The default number of items per page.
     */
    protected function defaultPerPage(): int
    {
        return config()->integer('pagination.default_per_page', 10);
    }

    /**
     * The minimum allowed value for per-page. Override in child requests to tighten limits.
     */
    protected function minPerPage(): int
    {
        return config()->integer('pagination.min_per_page', 1);
    }

    /**
     * The maximum allowed value for per-page. Override in child requests to tighten limits.
     */
    protected function maxPerPage(): int
    {
        return config()->integer('pagination.max_per_page', 100);
    }
}
