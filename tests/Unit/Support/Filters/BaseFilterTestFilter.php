<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Filters;

use App\Support\Filters\BaseFilter;
use Illuminate\Http\Request;
use Modules\IAM\Models\User;
use Override;

/**
 * @extends BaseFilter<User>
 */
final class BaseFilterTestFilter extends BaseFilter
{
    /**
     * @param  array{allowedFilters?: array<int, string>, allowedSorts?: array<int, string>, allowedFields?: array<int, string>, allowedIncludes?: array<int, string>, searchableColumns?: array<int, string>, exactMatchColumns?: array<int, string>}  $config
     */
    public function __construct(Request $request, private array $config = [])
    {
        parent::__construct($request);
    }

    #[Override]
    public function __invoke($query): void
    {
        if (array_key_exists('allowedFilters', $this->config)) {
            /** @var array<int, string> $allowedFilters */
            $allowedFilters = $this->config['allowedFilters'];
            $this->allowedFilters = $allowedFilters;
        }

        if (array_key_exists('allowedSorts', $this->config)) {
            /** @var array<int, string> $allowedSorts */
            $allowedSorts = $this->config['allowedSorts'];
            $this->allowedSorts = $allowedSorts;
        }

        if (array_key_exists('allowedFields', $this->config)) {
            /** @var array<int, string> $allowedFields */
            $allowedFields = $this->config['allowedFields'];
            $this->allowedFields = $allowedFields;
        }

        if (array_key_exists('allowedIncludes', $this->config)) {
            /** @var array<int, string> $allowedIncludes */
            $allowedIncludes = $this->config['allowedIncludes'];
            $this->allowedIncludes = $allowedIncludes;
        }

        if (array_key_exists('searchableColumns', $this->config)) {
            /** @var array<int, string> $searchableColumns */
            $searchableColumns = $this->config['searchableColumns'];
            $this->searchableColumns = $searchableColumns;
        }

        if (array_key_exists('exactMatchColumns', $this->config)) {
            /** @var array<int, string> $exactMatchColumns */
            $exactMatchColumns = $this->config['exactMatchColumns'];
            $this->exactMatchColumns = $exactMatchColumns;
        }

        parent::__invoke($query);
    }
}
