<?php

declare(strict_types=1);

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
abstract class BaseFilter
{
    protected string $searchParameter = 'search';

    /** @var array<int, string> */
    protected array $allowedFilters = [];

    /** @var array<int, string> */
    protected array $allowedSorts = [];

    public function __construct(protected Request $request) {}

    /**
     * @param  Builder<TModel>  $builder
     * @param  array<int, string>  $columns
     * @return Builder<TModel>
     */
    protected function applySearch(Builder $builder, string $value, array $columns): Builder
    {
        $tokens = $this->tokenizeSearch($value);

        if ($tokens === []) {
            return $builder;
        }

        return $builder->where(function (Builder $query) use ($tokens, $columns): void {
            foreach ($tokens as $token) {
                $query->where(function (Builder $q) use ($token, $columns): void {
                    foreach ($columns as $index => $column) {
                        if ($index === 0) {
                            $q->where($column, 'like', "%{$token}%");
                        } else {
                            $q->orWhere($column, 'like', "%{$token}%");
                        }
                    }
                });
            }
        });
    }

    /**
     * @param  Builder<TModel>  $builder
     * @return Builder<TModel>
     */
    public function apply(Builder $builder): Builder
    {
        $builder = $this->handleSearch($builder);

        $builder = $this->handleFilters($builder);

        $this->applySorting($builder);

        return $builder;
    }

    /**
     * @param  Builder<TModel>  $builder
     * @return Builder<TModel>
     */
    protected function handleSearch(Builder $builder): Builder
    {
        $searchValue = $this->request->query($this->searchParameter);

        if ($searchValue === null) {
            return $builder;
        }

        if (! is_string($searchValue)) {
            throw new InvalidArgumentException(sprintf(
                'Search parameter "%s" must be a string.',
                $this->searchParameter,
            ));
        }

        $searchValue = trim($searchValue);

        if ($searchValue === '') {
            return $builder;
        }

        return $this->search($builder, $searchValue);
    }

    /**
     * @param  Builder<TModel>  $builder
     * @return Builder<TModel>
     */
    protected function handleFilters(Builder $builder): Builder
    {
        $filters = $this->request->query('filter', []);

        if (! is_array($filters)) {
            return $builder;
        }

        foreach ($filters as $name => $value) {
            if ($name === $this->searchParameter) {
                continue;
            }

            if ($this->allowedFilters !== [] && ! in_array((string) $name, $this->allowedFilters, true)) {
                continue;
            }

            $method = Str::camel((string) $name);

            if ($value !== null && $value !== '' && method_exists($this, $method)) {
                $result = $this->{$method}($builder, $value);

                if ($result instanceof Builder) {
                    $builder = $result;
                }
            }
        }

        return $builder;
    }

    /**
     * @param  Builder<TModel>  $builder
     */
    protected function applySorting(Builder $builder): void
    {
        $sortParam = $this->request->query('sort', '');

        if (! is_string($sortParam) || trim($sortParam) === '') {
            $builder->orderBy($builder->getModel()->getQualifiedKeyName(), 'desc');

            return;
        }

        $columns = explode(',', $sortParam);
        $applied = false;

        foreach ($columns as $column) {
            $column = trim($column);

            if ($column === '') {
                continue;
            }

            $direction = 'asc';

            if (str_starts_with($column, '-')) {
                $direction = 'desc';
                $column = substr($column, 1);
            }

            if (in_array($column, $this->allowedSorts, true)) {
                $builder->orderBy($column, $direction);
                $applied = true;
            }
        }

        if (! $applied) {
            $builder->orderBy($builder->getModel()->getQualifiedKeyName(), 'desc');
        }
    }

    /**
     * @return array<int, string>
     */
    protected function tokenizeSearch(string $value): array
    {
        $tokens = explode(' ', $value);

        $filtered = array_filter($tokens, fn (string $token): bool => trim($token) !== '');

        return array_map(fn (string $token): string => addcslashes(trim($token), '%_'), array_values($filtered));
    }

    /**
     * @param  Builder<TModel>  $builder
     * @return Builder<TModel>
     */
    abstract public function search(Builder $builder, string $value): Builder;
}
