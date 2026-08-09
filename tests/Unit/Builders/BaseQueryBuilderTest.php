<?php

declare(strict_types=1);

use App\Builders\BaseQueryBuilder;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Log;
use Modules\IAM\Models\User;
use Tests\Unit\Builders\TestQueryBuilder;

covers(BaseQueryBuilder::class);

/**
 * Build a test builder with the given query string params and metadata config.
 *
 * @param  array<string, mixed>  $query
 * @param  array{allowedFilters?: array<int, string>, allowedSorts?: array<int, string>, allowedFields?: array<int, string>, allowedIncludes?: array<int, string>, searchableColumns?: array<int, string>, exactMatchColumns?: array<int, string>}  $config
 * @return TestQueryBuilder<User>
 */
function makeQueryBuilder(array $query = [], array $config = []): TestQueryBuilder
{
    request()->query->replace($query);

    $source = User::query();

    $builder = new TestQueryBuilder($source->getQuery())
        ->configure($config)
        ->setModel($source->getModel());

    foreach ($source->getModel()->getGlobalScopes() as $identifier => $scope) {
        if ($scope instanceof Scope) {
            $builder->withGlobalScope($identifier, $scope);
        }
    }

    return $builder;
}

beforeEach(function () {
    app()->detectEnvironment(fn () => 'testing');
});

describe('search', function () {
    it('applies LIKE on searchable columns', function () {
        $builder = makeQueryBuilder(
            query: ['search' => 'bob'],
            config: ['searchableColumns' => ['name', 'email']],
        );

        $sql = $builder->allowedSearch()->toSql();

        expect($sql)->toContain('like')
            ->toContain('?');
    });

    it('is skipped when param is empty', function () {
        $builder = makeQueryBuilder(config: ['searchableColumns' => ['name', 'email']]);

        $sql = $builder->allowedSearch()->toSql();

        expect($sql)->not->toContain('like');
    });

    it('is skipped when searchable columns is empty', function () {
        $builder = makeQueryBuilder(query: ['search' => 'bob']);

        $sql = $builder->allowedSearch()->toSql();

        expect($sql)->not->toContain('like');
    });
});

describe('filter', function () {
    it('applies LIKE for string values', function () {
        $builder = makeQueryBuilder(
            query: ['filter' => ['name' => 'bob']],
            config: ['allowedFilters' => ['name']],
        );

        $sql = $builder->allowedFilters()->toSql();

        expect($sql)->toContain('where')
            ->toContain('like');
    });

    it('applies exact match for exactMatchColumns', function () {
        $builder = makeQueryBuilder(
            query: ['filter' => ['status' => 'active']],
            config: ['allowedFilters' => ['status'], 'exactMatchColumns' => ['status']],
        );

        $sql = $builder->allowedFilters()->toSql();

        expect($sql)->toContain('where')
            ->toContain('= ?');
    });

    it('applies numeric exact match', function () {
        $builder = makeQueryBuilder(
            query: ['filter' => ['age' => '25']],
            config: ['allowedFilters' => ['age']],
        );

        $sql = $builder->allowedFilters()->toSql();

        expect($sql)->toContain('= ?');
    });

    it('applies WHERE IN for array values', function () {
        $builder = makeQueryBuilder(
            query: ['filter' => ['name' => ['bob', 'alice']]],
            config: ['allowedFilters' => ['name']],
        );

        $sql = $builder->allowedFilters()->toSql();

        expect($sql)->toContain('in');
    });

    it('handles operator prefix', function (string $prefix, string $column, string $value, string $expectSql) {
        $builder = makeQueryBuilder(
            query: ['filter' => [$column => $prefix.$value]],
            config: ['allowedFilters' => [$column]],
        );

        $sql = $builder->allowedFilters()->toSql();

        expect($sql)->toContain($expectSql);
    })->with('filterOperators');

    it('handles null value', function () {
        $builder = makeQueryBuilder(
            query: ['filter' => ['name' => 'null']],
            config: ['allowedFilters' => ['name']],
        );

        $sql = $builder->allowedFilters()->toSql();

        expect($sql)->toContain('is null');
    });

    it('handles !null value', function () {
        $builder = makeQueryBuilder(
            query: ['filter' => ['name' => '!null']],
            config: ['allowedFilters' => ['name']],
        );

        $sql = $builder->allowedFilters()->toSql();

        expect($sql)->toContain('is not null');
    });

    it('handles boolean true', function () {
        $builder = makeQueryBuilder(
            query: ['filter' => ['status' => 'true']],
            config: ['allowedFilters' => ['status']],
        );

        $sql = $builder->allowedFilters()->toSql();

        expect($sql)->toContain('= ?');
    });

    it('handles boolean false', function () {
        $builder = makeQueryBuilder(
            query: ['filter' => ['status' => 'false']],
            config: ['allowedFilters' => ['status']],
        );

        $sql = $builder->allowedFilters()->toSql();

        expect($sql)->toContain('= ?');
    });

    it('throws for unknown filter key in non-production', function () {
        $builder = makeQueryBuilder(query: ['filter' => ['unknown_field' => 'value']]);

        expect(fn () => $builder->allowedFilters())
            ->toThrow(InvalidArgumentException::class, 'BaseQueryBuilder: unknown filter key');
    });

    it('silently logs unknown keys in production', function () {
        app()->detectEnvironment(fn () => 'production');
        Log::shouldReceive('debug')->once();

        $builder = makeQueryBuilder(query: ['filter' => ['unknown_field' => 'value']]);

        $builder->allowedFilters();
    });
});

describe('sort', function () {
    it('applies default latest when no sort param', function () {
        $builder = makeQueryBuilder();

        $sql = $builder->allowedSorts()->toSql();

        expect($sql)->toContain('order by');
    });

    it('applies ascending order', function () {
        $builder = makeQueryBuilder(
            query: ['sort' => 'name'],
            config: ['allowedSorts' => ['name']],
        );

        $sql = $builder->allowedSorts()->toSql();

        expect($sql)->toContain('order by')
            ->toContain('asc');
    });

    it('applies descending order', function () {
        $builder = makeQueryBuilder(
            query: ['sort' => '-name'],
            config: ['allowedSorts' => ['name']],
        );

        $sql = $builder->allowedSorts()->toSql();

        expect($sql)->toContain('order by')
            ->toContain('desc');
    });

    it('handles multiple columns', function () {
        $builder = makeQueryBuilder(
            query: ['sort' => '-age,name'],
            config: ['allowedSorts' => ['age', 'name']],
        );

        $sql = $builder->allowedSorts()->toSql();

        expect($sql)->toContain('desc')
            ->toContain('asc');
    });

    it('throws for unknown column in non-production', function () {
        $builder = makeQueryBuilder(
            query: ['sort' => 'unknown_column'],
            config: ['allowedSorts' => ['name']],
        );

        expect(fn () => $builder->allowedSorts())
            ->toThrow(InvalidArgumentException::class, 'BaseQueryBuilder: unknown sort column');
    });
});

describe('fields', function () {
    it('applies sparse field selection', function () {
        $builder = makeQueryBuilder(
            query: ['fields' => ['users' => 'name,email']],
            config: ['allowedFields' => ['name', 'email']],
        );

        $sql = $builder->allowedFields()->toSql();

        expect($sql)->toContain('select')
            ->toContain('name')
            ->toContain('email');
    });

    it('always includes primary key', function () {
        $builder = makeQueryBuilder(
            query: ['fields' => ['users' => 'name']],
            config: ['allowedFields' => ['name']],
        );

        $sql = $builder->allowedFields()->toSql();

        expect($sql)->toContain('id');
    });

    it('throws for unknown field in non-production', function () {
        $builder = makeQueryBuilder(
            query: ['fields' => ['users' => 'unknown_col']],
            config: ['allowedFields' => ['name']],
        );

        expect(fn () => $builder->allowedFields())
            ->toThrow(InvalidArgumentException::class, 'BaseQueryBuilder: unknown fields');
    });
});

describe('includes', function () {
    it('applies eager loading', function () {
        $builder = makeQueryBuilder(
            query: ['include' => 'roles'],
            config: ['allowedIncludes' => ['roles']],
        );

        $query = $builder->allowedIncludes();

        expect($query->getEagerLoads())->toHaveKey('roles');
    });

    it('handles nested relations', function () {
        $builder = makeQueryBuilder(
            query: ['include' => 'roles.permissions'],
            config: ['allowedIncludes' => ['roles']],
        );

        $query = $builder->allowedIncludes();

        expect($query->getEagerLoads())->toHaveKey('roles.permissions');
    });

    it('throws for unknown include in non-production', function () {
        $builder = makeQueryBuilder(
            query: ['include' => 'unknown_relation'],
            config: ['allowedIncludes' => ['roles']],
        );

        expect(fn () => $builder->allowedIncludes())
            ->toThrow(InvalidArgumentException::class, 'BaseQueryBuilder: unknown includes');
    });
});

describe('trashed', function () {
    it('with includes soft deleted records', function () {
        $builder = makeQueryBuilder(
            query: ['filter' => ['trashed' => 'with']],
            config: ['allowedFilters' => ['trashed']],
        );

        $sql = $builder->allowedFilters()->toSql();

        expect($sql)->not->toContain('"deleted_at" is null');
    });

    it('only shows only soft deleted records', function () {
        $builder = makeQueryBuilder(
            query: ['filter' => ['trashed' => 'only']],
            config: ['allowedFilters' => ['trashed']],
        );

        $sql = $builder->allowedFilters()->toSql();

        expect($sql)->toContain('is not null');
    });

    it('ignores invalid values', function () {
        $builder = makeQueryBuilder(
            query: ['filter' => ['trashed' => 'invalid']],
            config: ['allowedFilters' => ['trashed']],
        );

        $sql = $builder->allowedFilters()->toSql();

        expect($sql)->toContain('"deleted_at" is null');
    });
});

describe('report warning', function () {
    it('throws in non-production environment', function () {
        $builder = makeQueryBuilder();

        $reflection = new ReflectionMethod(BaseQueryBuilder::class, 'reportWarning');

        expect(fn () => $reflection->invoke($builder, 'test warning'))
            ->toThrow(InvalidArgumentException::class, 'test warning');
    });

    it('logs in production environment', function () {
        app()->detectEnvironment(fn () => 'production');

        $builder = makeQueryBuilder(query: ['filter' => ['unknown_key' => 'value']]);

        expect(fn () => $builder->allowedFilters())->not->toThrow(InvalidArgumentException::class);
    });
});

describe('value truncation', function () {
    it('truncates long filter values', function () {
        $builder = makeQueryBuilder(
            query: ['filter' => ['name' => str_repeat('a', 300)]],
            config: ['allowedFilters' => ['name']],
        );

        $sql = $builder->allowedFilters()->toSql();

        expect($sql)->toContain('like');
    });
});
