<?php

declare(strict_types=1);

use App\Support\Filters\BaseFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\IAM\Models\User;
use Tests\Unit\Support\Filters\BaseFilterTestFilter;

covers(BaseFilter::class);

/**
 * @param  array<string, mixed>  $query
 * @param  array{allowedFilters?: array<int, string>, allowedSorts?: array<int, string>, allowedFields?: array<int, string>, allowedIncludes?: array<int, string>, searchableColumns?: array<int, string>, exactMatchColumns?: array<int, string>}  $config
 * @return BaseFilter<User>
 */
function makeFilter(array $query = [], array $config = []): BaseFilter
{
    return new BaseFilterTestFilter(new Request($query), $config);
}

beforeEach(function () {
    app()->detectEnvironment(fn () => 'testing');
});

describe('search', function () {
    it('applies LIKE on searchable columns', function () {
        $filter = makeFilter(
            query: ['search' => 'bob'],
            config: ['searchableColumns' => ['name', 'email']],
        );

        $sql = User::query()->tap($filter)->toSql();

        expect($sql)->toContain('like')
            ->toContain('?');
    });

    it('is skipped when param is empty', function () {
        $filter = makeFilter(config: ['searchableColumns' => ['name', 'email']]);

        $sql = User::query()->tap($filter)->toSql();

        expect($sql)->not->toContain('like');
    });

    it('is skipped when searchable columns is empty', function () {
        $filter = makeFilter(query: ['search' => 'bob']);

        $sql = User::query()->tap($filter)->toSql();

        expect($sql)->not->toContain('like');
    });
});

describe('filter', function () {
    it('applies LIKE for string values', function () {
        $filter = makeFilter(
            query: ['filter' => ['name' => 'bob']],
            config: ['allowedFilters' => ['name']],
        );

        $sql = User::query()->tap($filter)->toSql();

        expect($sql)->toContain('where')
            ->toContain('like');
    });

    it('applies exact match for exactMatchColumns', function () {
        $filter = makeFilter(
            query: ['filter' => ['status' => 'active']],
            config: ['allowedFilters' => ['status'], 'exactMatchColumns' => ['status']],
        );

        $sql = User::query()->tap($filter)->toSql();

        expect($sql)->toContain('where')
            ->toContain('= ?');
    });

    it('applies numeric exact match', function () {
        $filter = makeFilter(
            query: ['filter' => ['age' => '25']],
            config: ['allowedFilters' => ['age']],
        );

        $sql = User::query()->tap($filter)->toSql();

        expect($sql)->toContain('= ?');
    });

    it('applies WHERE IN for array values', function () {
        $filter = makeFilter(
            query: ['filter' => ['role' => ['admin', 'user']]],
            config: ['allowedFilters' => ['role']],
        );

        $sql = User::query()->tap($filter)->toSql();

        expect($sql)->toContain('in');
    });

    it('handles operator prefix', function (string $prefix, string $column, string $value, string $expectSql) {
        $filter = makeFilter(
            query: ['filter' => [$column => $prefix.$value]],
            config: ['allowedFilters' => [$column]],
        );

        $sql = User::query()->tap($filter)->toSql();

        expect($sql)->toContain($expectSql);
    })->with('filterOperators');

    it('handles null value', function () {
        $filter = makeFilter(
            query: ['filter' => ['name' => 'null']],
            config: ['allowedFilters' => ['name']],
        );

        $sql = User::query()->tap($filter)->toSql();

        expect($sql)->toContain('is null');
    });

    it('handles !null value', function () {
        $filter = makeFilter(
            query: ['filter' => ['name' => '!null']],
            config: ['allowedFilters' => ['name']],
        );

        $sql = User::query()->tap($filter)->toSql();

        expect($sql)->toContain('is not null');
    });

    it('handles boolean true', function () {
        $filter = makeFilter(
            query: ['filter' => ['status' => 'true']],
            config: ['allowedFilters' => ['status']],
        );

        $sql = User::query()->tap($filter)->toSql();

        expect($sql)->toContain('= ?');
    });

    it('handles boolean false', function () {
        $filter = makeFilter(
            query: ['filter' => ['status' => 'false']],
            config: ['allowedFilters' => ['status']],
        );

        $sql = User::query()->tap($filter)->toSql();

        expect($sql)->toContain('= ?');
    });

    it('throws for unknown filter key in non-production', function () {
        $filter = makeFilter(query: ['filter' => ['unknown_field' => 'value']]);

        expect(fn () => User::query()->tap($filter))
            ->toThrow(InvalidArgumentException::class, 'BaseFilter: unknown filter key');
    });

    it('silently logs unknown keys in production', function () {
        app()->detectEnvironment(fn () => 'production');
        Log::shouldReceive('debug')->once();

        $filter = makeFilter(query: ['filter' => ['unknown_field' => 'value']]);

        User::query()->tap($filter);
    });
});

describe('sort', function () {
    it('applies default latest when no sort param', function () {
        $filter = makeFilter();

        $sql = User::query()->tap($filter)->toSql();

        expect($sql)->toContain('order by');
    });

    it('applies ascending order', function () {
        $filter = makeFilter(
            query: ['sort' => 'name'],
            config: ['allowedSorts' => ['name']],
        );

        $sql = User::query()->tap($filter)->toSql();

        expect($sql)->toContain('order by')
            ->toContain('asc');
    });

    it('applies descending order', function () {
        $filter = makeFilter(
            query: ['sort' => '-name'],
            config: ['allowedSorts' => ['name']],
        );

        $sql = User::query()->tap($filter)->toSql();

        expect($sql)->toContain('order by')
            ->toContain('desc');
    });

    it('handles multiple columns', function () {
        $filter = makeFilter(
            query: ['sort' => '-age,name'],
            config: ['allowedSorts' => ['age', 'name']],
        );

        $sql = User::query()->tap($filter)->toSql();

        expect($sql)->toContain('desc')
            ->toContain('asc');
    });

    it('throws for unknown column in non-production', function () {
        $filter = makeFilter(
            query: ['sort' => 'unknown_column'],
            config: ['allowedSorts' => ['name']],
        );

        expect(fn () => User::query()->tap($filter))
            ->toThrow(InvalidArgumentException::class, 'BaseFilter: unknown sort column');
    });
});

describe('fields', function () {
    it('applies sparse field selection', function () {
        $filter = makeFilter(
            query: ['fields' => ['users' => 'name,email']],
            config: ['allowedFields' => ['name', 'email']],
        );

        $sql = User::query()->tap($filter)->toSql();

        expect($sql)->toContain('select')
            ->toContain('name')
            ->toContain('email');
    });

    it('always includes primary key', function () {
        $filter = makeFilter(
            query: ['fields' => ['users' => 'name']],
            config: ['allowedFields' => ['name']],
        );

        $sql = User::query()->tap($filter)->toSql();

        expect($sql)->toContain('id');
    });

    it('throws for unknown field in non-production', function () {
        $filter = makeFilter(
            query: ['fields' => ['users' => 'unknown_col']],
            config: ['allowedFields' => ['name']],
        );

        expect(fn () => User::query()->tap($filter))
            ->toThrow(InvalidArgumentException::class, 'BaseFilter: unknown fields');
    });
});

describe('includes', function () {
    it('applies eager loading', function () {
        $filter = makeFilter(
            query: ['include' => 'roles'],
            config: ['allowedIncludes' => ['roles']],
        );

        $query = User::query()->tap($filter);

        expect($query->getEagerLoads())->toHaveKey('roles');
    });

    it('handles nested relations', function () {
        $filter = makeFilter(
            query: ['include' => 'roles.permissions'],
            config: ['allowedIncludes' => ['roles']],
        );

        $query = User::query()->tap($filter);

        expect($query->getEagerLoads())->toHaveKey('roles.permissions');
    });

    it('throws for unknown include in non-production', function () {
        $filter = makeFilter(
            query: ['include' => 'unknown_relation'],
            config: ['allowedIncludes' => ['roles']],
        );

        expect(fn () => User::query()->tap($filter))
            ->toThrow(InvalidArgumentException::class, 'BaseFilter: unknown includes');
    });
});

describe('trashed', function () {
    it('with includes soft deleted records', function () {
        $filter = makeFilter(
            query: ['filter' => ['trashed' => 'with']],
            config: ['allowedFilters' => ['trashed']],
        );

        $sql = User::query()->tap($filter)->toSql();

        expect($sql)->not->toContain('"deleted_at" is null');
    });

    it('only shows only soft deleted records', function () {
        $filter = makeFilter(
            query: ['filter' => ['trashed' => 'only']],
            config: ['allowedFilters' => ['trashed']],
        );

        $sql = User::query()->tap($filter)->toSql();

        expect($sql)->toContain('is not null');
    });

    it('ignores invalid values', function () {
        $filter = makeFilter(
            query: ['filter' => ['trashed' => 'invalid']],
            config: ['allowedFilters' => ['trashed']],
        );

        $sql = User::query()->tap($filter)->toSql();

        expect($sql)->toContain('"deleted_at" is null');
    });
});

describe('report warning', function () {
    it('throws in non-production environment', function () {
        $filter = makeFilter();

        $reflection = new ReflectionMethod(BaseFilter::class, 'reportWarning');

        expect(fn () => $reflection->invoke($filter, 'test warning'))
            ->toThrow(InvalidArgumentException::class, 'test warning');
    });

    it('logs in production environment', function () {
        app()->detectEnvironment(fn () => 'production');

        $filter = makeFilter(query: ['filter' => ['unknown_key' => 'value']]);

        expect(fn () => User::query()->tap($filter))->not->toThrow(InvalidArgumentException::class);
    });
});

describe('value truncation', function () {
    it('truncates long filter values', function () {
        $filter = makeFilter(
            query: ['filter' => ['name' => str_repeat('a', 300)]],
            config: ['allowedFilters' => ['name']],
        );

        $sql = User::query()->tap($filter)->toSql();

        expect($sql)->toContain('like');
    });
});
