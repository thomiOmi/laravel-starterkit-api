<?php

declare(strict_types=1);

use App\Support\Filters\BaseFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\IAM\Models\User;

function makeFilter(array $query = [], array $config = []): TestFilter
{
    return new TestFilter(new Request($query), $config);
}

beforeEach(function () {
    app()->detectEnvironment(fn () => 'testing');
});

// ---------- Search ----------

test('search applies LIKE on searchable columns', function () {
    $filter = makeFilter(
        query: ['search' => 'bob'],
        config: ['searchableColumns' => ['name', 'email']],
    );

    $sql = User::query()->tap($filter)->toSql();

    expect($sql)->toContain('like');
    expect($sql)->toContain('?');
});

test('search is skipped when param is empty', function () {
    $filter = makeFilter(config: ['searchableColumns' => ['name', 'email']]);

    $sql = User::query()->tap($filter)->toSql();

    expect($sql)->not->toContain('like');
});

test('search is skipped when searchable columns is empty', function () {
    $filter = makeFilter(query: ['search' => 'bob']);

    $sql = User::query()->tap($filter)->toSql();

    expect($sql)->not->toContain('like');
});

// ---------- Filter ----------

test('filter applies LIKE for string values', function () {
    $filter = makeFilter(
        query: ['filter' => ['name' => 'bob']],
        config: ['allowedFilters' => ['name']],
    );

    $sql = User::query()->tap($filter)->toSql();

    expect($sql)->toContain('where');
    expect($sql)->toContain('like');
});

test('filter applies exact match for exactMatchColumns', function () {
    $filter = makeFilter(
        query: ['filter' => ['status' => 'active']],
        config: ['allowedFilters' => ['status'], 'exactMatchColumns' => ['status']],
    );

    $sql = User::query()->tap($filter)->toSql();

    expect($sql)->toContain('where');
    expect($sql)->toContain('= ?');
});

test('filter applies numeric exact match', function () {
    $filter = makeFilter(
        query: ['filter' => ['age' => '25']],
        config: ['allowedFilters' => ['age']],
    );

    $sql = User::query()->tap($filter)->toSql();

    expect($sql)->toContain('= ?');
});

test('filter applies WHERE IN for array values', function () {
    $filter = makeFilter(
        query: ['filter' => ['role' => ['admin', 'user']]],
        config: ['allowedFilters' => ['role']],
    );

    $sql = User::query()->tap($filter)->toSql();

    expect($sql)->toContain('in');
});

test('filter handles operator prefix eq:', function () {
    $filter = makeFilter(
        query: ['filter' => ['name' => 'eq:John']],
        config: ['allowedFilters' => ['name']],
    );

    $sql = User::query()->tap($filter)->toSql();

    expect($sql)->toContain('= ?');
});

test('filter handles operator prefix neq:', function () {
    $filter = makeFilter(
        query: ['filter' => ['name' => 'neq:John']],
        config: ['allowedFilters' => ['name']],
    );

    $sql = User::query()->tap($filter)->toSql();

    expect($sql)->toContain('!= ?');
});

test('filter handles operator prefix gt:', function () {
    $filter = makeFilter(
        query: ['filter' => ['age' => 'gt:18']],
        config: ['allowedFilters' => ['age']],
    );

    $sql = User::query()->tap($filter)->toSql();

    expect($sql)->toContain('> ?');
});

test('filter handles operator prefix gte:', function () {
    $filter = makeFilter(
        query: ['filter' => ['age' => 'gte:18']],
        config: ['allowedFilters' => ['age']],
    );

    $sql = User::query()->tap($filter)->toSql();

    expect($sql)->toContain('>= ?');
});

test('filter handles operator prefix lt:', function () {
    $filter = makeFilter(
        query: ['filter' => ['age' => 'lt:18']],
        config: ['allowedFilters' => ['age']],
    );

    $sql = User::query()->tap($filter)->toSql();

    expect($sql)->toContain('< ?');
});

test('filter handles operator prefix lte:', function () {
    $filter = makeFilter(
        query: ['filter' => ['age' => 'lte:18']],
        config: ['allowedFilters' => ['age']],
    );

    $sql = User::query()->tap($filter)->toSql();

    expect($sql)->toContain('<= ?');
});

test('filter handles operator prefix like:', function () {
    $filter = makeFilter(
        query: ['filter' => ['name' => 'like:Al%']],
        config: ['allowedFilters' => ['name']],
    );

    $sql = User::query()->tap($filter)->toSql();

    expect($sql)->toContain('like ?');
});

test('filter handles null value', function () {
    $filter = makeFilter(
        query: ['filter' => ['name' => 'null']],
        config: ['allowedFilters' => ['name']],
    );

    $sql = User::query()->tap($filter)->toSql();

    expect($sql)->toContain('is null');
});

test('filter handles !null value', function () {
    $filter = makeFilter(
        query: ['filter' => ['name' => '!null']],
        config: ['allowedFilters' => ['name']],
    );

    $sql = User::query()->tap($filter)->toSql();

    expect($sql)->toContain('is not null');
});

test('filter handles boolean true', function () {
    $filter = makeFilter(
        query: ['filter' => ['status' => 'true']],
        config: ['allowedFilters' => ['status']],
    );

    $sql = User::query()->tap($filter)->toSql();

    expect($sql)->toContain('= ?');
});

test('filter handles boolean false', function () {
    $filter = makeFilter(
        query: ['filter' => ['status' => 'false']],
        config: ['allowedFilters' => ['status']],
    );

    $sql = User::query()->tap($filter)->toSql();

    expect($sql)->toContain('= ?');
});

test('filter throws for unknown filter key in non-production', function () {
    $filter = makeFilter(query: ['filter' => ['unknown_field' => 'value']]);

    expect(fn () => User::query()->tap($filter))
        ->toThrow(InvalidArgumentException::class, 'BaseFilter: unknown filter key');
});

test('filter silently logs unknown keys in production', function () {
    app()->detectEnvironment(fn () => 'production');
    Log::shouldReceive('debug')->once();

    $filter = makeFilter(query: ['filter' => ['unknown_field' => 'value']]);

    User::query()->tap($filter);
});

// ---------- Sort ----------

test('sort applies default latest when no sort param', function () {
    $filter = makeFilter();

    $sql = User::query()->tap($filter)->toSql();

    expect($sql)->toContain('order by');
});

test('sort applies ascending order', function () {
    $filter = makeFilter(
        query: ['sort' => 'name'],
        config: ['allowedSorts' => ['name']],
    );

    $sql = User::query()->tap($filter)->toSql();

    expect($sql)->toContain('order by');
    expect($sql)->toContain('asc');
});

test('sort applies descending order', function () {
    $filter = makeFilter(
        query: ['sort' => '-name'],
        config: ['allowedSorts' => ['name']],
    );

    $sql = User::query()->tap($filter)->toSql();

    expect($sql)->toContain('order by');
    expect($sql)->toContain('desc');
});

test('sort handles multiple columns', function () {
    $filter = makeFilter(
        query: ['sort' => '-age,name'],
        config: ['allowedSorts' => ['age', 'name']],
    );

    $sql = User::query()->tap($filter)->toSql();

    expect($sql)->toContain('desc');
    expect($sql)->toContain('asc');
});

test('sort throws for unknown column in non-production', function () {
    $filter = makeFilter(
        query: ['sort' => 'unknown_column'],
        config: ['allowedSorts' => ['name']],
    );

    expect(fn () => User::query()->tap($filter))
        ->toThrow(InvalidArgumentException::class, 'BaseFilter: unknown sort column');
});

// ---------- Fields ----------

test('fields applies sparse field selection', function () {
    $filter = makeFilter(
        query: ['fields' => ['users' => 'name,email']],
        config: ['allowedFields' => ['name', 'email']],
    );

    $sql = User::query()->tap($filter)->toSql();

    expect($sql)->toContain('select');
    expect($sql)->toContain('name');
    expect($sql)->toContain('email');
});

test('fields always includes primary key', function () {
    $filter = makeFilter(
        query: ['fields' => ['users' => 'name']],
        config: ['allowedFields' => ['name']],
    );

    $sql = User::query()->tap($filter)->toSql();

    expect($sql)->toContain('id');
});

test('fields throws for unknown field in non-production', function () {
    $filter = makeFilter(
        query: ['fields' => ['users' => 'unknown_col']],
        config: ['allowedFields' => ['name']],
    );

    expect(fn () => User::query()->tap($filter))
        ->toThrow(InvalidArgumentException::class, 'BaseFilter: unknown fields');
});

// ---------- Includes ----------

test('includes applies eager loading', function () {
    $filter = makeFilter(
        query: ['include' => 'roles'],
        config: ['allowedIncludes' => ['roles']],
    );

    $query = User::query()->tap($filter);

    expect($query->getEagerLoads())->toHaveKey('roles');
});

test('includes handles nested relations', function () {
    $filter = makeFilter(
        query: ['include' => 'roles.permissions'],
        config: ['allowedIncludes' => ['roles']],
    );

    $query = User::query()->tap($filter);

    expect($query->getEagerLoads())->toHaveKey('roles.permissions');
});

test('includes throws for unknown include in non-production', function () {
    $filter = makeFilter(
        query: ['include' => 'unknown_relation'],
        config: ['allowedIncludes' => ['roles']],
    );

    expect(fn () => User::query()->tap($filter))
        ->toThrow(InvalidArgumentException::class, 'BaseFilter: unknown includes');
});

// ---------- Trashed ----------

test('trashed with includes soft deleted records', function () {
    $filter = makeFilter(
        query: ['filter' => ['trashed' => 'with']],
        config: ['allowedFilters' => ['trashed']],
    );

    $sql = User::query()->tap($filter)->toSql();

    expect($sql)->not->toContain('"deleted_at" is null');
});

test('trashed only shows only soft deleted records', function () {
    $filter = makeFilter(
        query: ['filter' => ['trashed' => 'only']],
        config: ['allowedFilters' => ['trashed']],
    );

    $sql = User::query()->tap($filter)->toSql();

    expect($sql)->toContain('is not null');
});

test('trashed ignores invalid values', function () {
    $filter = makeFilter(
        query: ['filter' => ['trashed' => 'invalid']],
        config: ['allowedFilters' => ['trashed']],
    );

    $sql = User::query()->tap($filter)->toSql();

    expect($sql)->toContain('"deleted_at" is null');
});

// ---------- Report Warning ----------

test('reportWarning throws in non-production environment', function () {
    $filter = makeFilter();

    $reflection = new ReflectionMethod(BaseFilter::class, 'reportWarning');
    $reflection->setAccessible(true);

    expect(fn () => $reflection->invoke($filter, 'test warning'))
        ->toThrow(InvalidArgumentException::class, 'test warning');
});

test('reportWarning logs in production environment', function () {
    app()->detectEnvironment(fn () => 'production');

    $filter = makeFilter(query: ['filter' => ['unknown_key' => 'value']]);

    expect(fn () => User::query()->tap($filter))->not->toThrow(InvalidArgumentException::class);
});

// ---------- Truncation ----------

test('truncates long filter values', function () {
    $filter = makeFilter(
        query: ['filter' => ['name' => str_repeat('a', 300)]],
        config: ['allowedFilters' => ['name']],
    );

    $sql = User::query()->tap($filter)->toSql();

    expect($sql)->toContain('like');
});

// ---------- Concrete test filter ----------

class TestFilter extends BaseFilter
{
    private array $config;

    public function __construct(Request $request, array $config = [])
    {
        $this->config = $config;

        parent::__construct($request);
    }

    public function __invoke($query): void
    {
        if (array_key_exists('allowedFilters', $this->config)) {
            $this->allowedFilters = $this->config['allowedFilters'];
        }

        if (array_key_exists('allowedSorts', $this->config)) {
            $this->allowedSorts = $this->config['allowedSorts'];
        }

        if (array_key_exists('allowedFields', $this->config)) {
            $this->allowedFields = $this->config['allowedFields'];
        }

        if (array_key_exists('allowedIncludes', $this->config)) {
            $this->allowedIncludes = $this->config['allowedIncludes'];
        }

        if (array_key_exists('searchableColumns', $this->config)) {
            $this->searchableColumns = $this->config['searchableColumns'];
        }

        if (array_key_exists('exactMatchColumns', $this->config)) {
            $this->exactMatchColumns = $this->config['exactMatchColumns'];
        }

        parent::__invoke($query);
    }
}
