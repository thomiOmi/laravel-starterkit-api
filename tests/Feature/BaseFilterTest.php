<?php

declare(strict_types=1);

use App\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Role\Database\Seeders\RoleSeeder;
use Modules\User\Models\User;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Route::get('/api/test-filter', function (Request $request) {
        $filter = new class($request) extends BaseFilter
        {
            protected array $allowedFilters = ['role', 'status'];

            protected array $allowedSorts = ['name', 'email', 'created_at'];

            public bool $searchCalled = false;

            public ?string $searchValue = null;

            /** @var array<int, string> */
            public array $filterMethodsCalled = [];

            public function search(Builder $builder, string $value): Builder
            {
                $this->searchCalled = true;
                $this->searchValue = $value;

                return $builder->where('name', 'like', "%{$value}%");
            }

            public function role(Builder $builder, mixed $value): Builder
            {
                $this->filterMethodsCalled[] = 'role';

                if (! is_string($value)) {
                    return $builder;
                }

                return $builder->whereRelation('roles', 'name', $value);
            }

            public function status(Builder $builder, mixed $value): Builder
            {
                $this->filterMethodsCalled[] = 'status';

                return $builder;
            }
        };

        $builder = User::query();
        $builder = $filter->apply($builder);

        return response()->json([
            'sql' => $builder->toSql(),
            'bindings' => $builder->getBindings(),
            'search_called' => $filter->searchCalled,
            'search_value' => $filter->searchValue,
            'filter_methods_called' => $filter->filterMethodsCalled,
        ]);
    })->middleware('api');
});

it('applies global search parameter', function () {
    User::factory()->create(['name' => 'Budi Santoso']);

    $response = $this->getJson('/api/test-filter?search=Budi');

    $response->assertSuccessful();
    $data = $response->json();

    expect($data['search_called'])->toBeTrue();
    expect($data['search_value'])->toBe('Budi');
    expect($data['sql'])->toContain('like');
});

it('applies multi-keyword search with tokenization', function () {
    $response = $this->getJson('/api/test-filter?search=Budi%20Santoso');

    $response->assertSuccessful();
    $data = $response->json();

    expect($data['search_called'])->toBeTrue();
    expect($data['search_value'])->toBe('Budi Santoso');
});

it('rejects non-string search parameter', function () {
    $response = $this->getJson('/api/test-filter?search[]=invalid');

    $response->assertStatus(Response::HTTP_BAD_REQUEST);
});

it('ignores empty search parameter', function () {
    $response = $this->getJson('/api/test-filter?search=');

    $response->assertSuccessful();
    $data = $response->json();

    expect($data['search_called'])->toBeFalse();
});

it('applies allowed filter via filter parameter', function () {
    User::factory()->create();
    $admin = User::factory()->create();
    $admin->assignRole('super-admin');

    $response = $this->getJson('/api/test-filter?filter[role]=super-admin');

    $response->assertSuccessful();
    $data = $response->json();

    expect($data['filter_methods_called'])->toContain('role');
});

it('ignores disallowed filter parameter', function () {
    $response = $this->getJson('/api/test-filter?filter[unknown]=value');

    $response->assertSuccessful();
    $data = $response->json();

    expect($data['filter_methods_called'])->toBeEmpty();
});

it('skips filter key matching search parameter', function () {
    $response = $this->getJson('/api/test-filter?filter[search]=Budi');

    $response->assertSuccessful();
    $data = $response->json();

    expect($data['search_called'])->toBeFalse();
    expect($data['filter_methods_called'])->not->toContain('search');
});

it('applies single-column sorting', function () {
    User::factory()->count(3)->create();

    $response = $this->getJson('/api/test-filter?sort=name');

    $response->assertSuccessful();
    $data = $response->json();

    expect($data['sql'])->toContain('order by');
    expect($data['sql'])->toContain('asc');
});

it('applies multi-column JSON:API sorting', function () {
    User::factory()->count(3)->create();

    $response = $this->getJson('/api/test-filter?sort=-created_at,name');

    $response->assertSuccessful();
    $data = $response->json();

    expect($data['sql'])->toContain('order by');
});

it('falls back to default sort when no sort parameter', function () {
    $response = $this->getJson('/api/test-filter');

    $response->assertSuccessful();
    $data = $response->json();

    expect($data['sql'])->toContain('order by');
    expect($data['sql'])->toContain('desc');
});

it('ignores disallowed sort column and uses default', function () {
    $response = $this->getJson('/api/test-filter?sort=password');

    $response->assertSuccessful();
    $data = $response->json();

    expect($data['sql'])->toContain('order by');
    expect($data['sql'])->toContain('desc');
    expect($data['sql'])->not->toContain('password');
});

it('tokenizes search string correctly', function () {
    $request = Request::create('/test', 'GET');
    $filter = new class($request) extends BaseFilter
    {
        public function search(Builder $builder, string $value): Builder
        {
            return $builder;
        }

        public function exposeTokenize(string $value): array
        {
            return $this->tokenizeSearch($value);
        }
    };

    $tokens = $filter->exposeTokenize('Budi Santomo');

    expect($tokens)->toBe(['Budi', 'Santomo']);
});

it('escapes SQL wildcards in search tokens', function () {
    $request = Request::create('/test', 'GET');
    $filter = new class($request) extends BaseFilter
    {
        public function search(Builder $builder, string $value): Builder
        {
            return $builder;
        }

        public function exposeTokenize(string $value): array
        {
            return $this->tokenizeSearch($value);
        }
    };

    $tokens = $filter->exposeTokenize('test%_value');

    expect($tokens)->toBe(['test\%\_value']);
});

it('handles multiple spaces in search string', function () {
    $request = Request::create('/test', 'GET');
    $filter = new class($request) extends BaseFilter
    {
        public function search(Builder $builder, string $value): Builder
        {
            return $builder;
        }

        public function exposeTokenize(string $value): array
        {
            return $this->tokenizeSearch($value);
        }
    };

    $tokens = $filter->exposeTokenize('Budi   Santoso  ');

    expect($tokens)->toBe(['Budi', 'Santoso']);
});

it('combines search with filter parameter', function () {
    $response = $this->getJson('/api/test-filter?search=Budi&filter[role]=super-admin');

    $response->assertSuccessful();
    $data = $response->json();

    expect($data['search_called'])->toBeTrue();
    expect($data['filter_methods_called'])->toContain('role');
});

it('combines search with sort parameter', function () {
    $response = $this->getJson('/api/test-filter?search=Budi&sort=-created_at');

    $response->assertSuccessful();
    $data = $response->json();

    expect($data['search_called'])->toBeTrue();
    expect($data['sql'])->toContain('order by');
});

it('selects only allowed fields via sparse fieldset', function () {
    $request = Request::create('/test', 'GET', ['fields' => ['users' => 'name,email']]);

    $filter = new class($request) extends BaseFilter
    {
        protected array $allowedFields = ['id', 'name', 'email', 'created_at'];

        public function search(Builder $builder, string $value): Builder
        {
            return $builder;
        }
    };

    $builder = $filter->apply(User::query());

    $sql = $builder->toSql();

    expect($sql)->toContain('`name`');
    expect($sql)->toContain('`email`');
    expect($sql)->toContain('`id`');
    expect($sql)->not->toContain('`created_at`');
});

it('always includes the primary key in sparse fieldset', function () {
    $request = Request::create('/test', 'GET', ['fields' => ['users' => 'name']]);

    $filter = new class($request) extends BaseFilter
    {
        protected array $allowedFields = ['id', 'name', 'email'];

        public function search(Builder $builder, string $value): Builder
        {
            return $builder;
        }
    };

    $builder = $filter->apply(User::query());

    $sql = $builder->toSql();

    expect($sql)->toContain('`id`');
    expect($sql)->toContain('`name`');
});

it('ignores fields not in allowedFields list', function () {
    $request = Request::create('/test', 'GET', ['fields' => ['users' => 'id,password,secret']]);

    $filter = new class($request) extends BaseFilter
    {
        protected array $allowedFields = ['id', 'name', 'email'];

        public function search(Builder $builder, string $value): Builder
        {
            return $builder;
        }
    };

    $builder = $filter->apply(User::query());

    $sql = $builder->toSql();

    expect($sql)->toContain('`id`');
    expect($sql)->not->toContain('password');
    expect($sql)->not->toContain('secret');
});

it('passes through all fields when allowedFields is empty', function () {
    $request = Request::create('/test', 'GET', ['fields' => ['users' => 'name,email']]);

    $filter = new class($request) extends BaseFilter
    {
        protected array $allowedFields = [];

        public function search(Builder $builder, string $value): Builder
        {
            return $builder;
        }
    };

    $builder = $filter->apply(User::query());

    $sql = $builder->toSql();

    expect($sql)->toContain('`name`');
    expect($sql)->toContain('`email`');
    expect($sql)->toContain('`id`');
});

it('uses fieldsKey when specified instead of table name', function () {
    $request = Request::create('/test', 'GET', ['fields' => ['custom' => 'id,name']]);

    $filter = new class($request) extends BaseFilter
    {
        protected array $allowedFields = ['id', 'name'];

        protected ?string $fieldsKey = 'custom';

        public function search(Builder $builder, string $value): Builder
        {
            return $builder;
        }
    };

    $builder = $filter->apply(User::query());

    $sql = $builder->toSql();

    expect($sql)->toContain('`id`');
    expect($sql)->toContain('`name`');
});

it('ignores sparse fieldset when fields parameter is missing', function () {
    $request = Request::create('/test', 'GET');

    $filter = new class($request) extends BaseFilter
    {
        protected array $allowedFields = ['id', 'name', 'email'];

        public function search(Builder $builder, string $value): Builder
        {
            return $builder;
        }
    };

    $builder = $filter->apply(User::query());

    $sql = $builder->toSql();

    expect($sql)->toContain('select *');
});

it('ignores sparse fieldset when fields value is empty', function () {
    $request = Request::create('/test', 'GET', ['fields' => ['users' => '']]);

    $filter = new class($request) extends BaseFilter
    {
        protected array $allowedFields = ['id', 'name', 'email'];

        public function search(Builder $builder, string $value): Builder
        {
            return $builder;
        }
    };

    $builder = $filter->apply(User::query());

    $sql = $builder->toSql();

    expect($sql)->toContain('select *');
});
