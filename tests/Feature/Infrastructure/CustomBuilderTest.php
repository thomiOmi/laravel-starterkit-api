<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure;

use App\Http\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

uses(RefreshDatabase::class);

class MockFilterModel extends Model
{
    protected $table = 'mock_filter_models';

    protected $fillable = ['name', 'email', 'category', 'is_active', 'score'];
}

test('native filter applies LIKE via auto-mapping', function () {
    Schema::create('mock_filter_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->string('category');
        $table->timestamps();
    });

    MockFilterModel::create(['name' => 'Alice', 'email' => 'alice@example.com', 'category' => 'A']);
    MockFilterModel::create(['name' => 'Bob', 'email' => 'bob@example.com', 'category' => 'B']);

    $request = new Request(['filter' => ['name' => 'bob']]);

    $results = MockFilterModel::query()->tap(new class($request) extends BaseFilter
    {
        protected array $allowedFilters = ['name'];
    })->get();

    expect($results)->toHaveCount(1)
        ->and($results[0]->name)->toBe('Bob');
});

test('native filter throws on unknown filters in non-production', function () {
    $request = new Request(['filter' => ['email' => 'alice@example.com']]);

    $filter = new class($request) extends BaseFilter
    {
        protected array $allowedFilters = ['name'];
    };

    expect(fn () => MockFilterModel::query()->tap($filter)->get())
        ->toThrow(InvalidArgumentException::class, 'unknown filter key');
});

test('native filter applies single-column sort and fields', function () {
    Schema::create('mock_filter_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->string('category');
        $table->timestamps();
    });

    MockFilterModel::create(['name' => 'Alice', 'email' => 'alice@example.com', 'category' => 'A']);
    MockFilterModel::create(['name' => 'Bob', 'email' => 'bob@example.com', 'category' => 'B']);

    $request = Request::create('/?sort=-name&fields[mock_filter_models]=name'); // name DESC

    $results = MockFilterModel::query()->tap(new class($request) extends BaseFilter
    {
        protected array $allowedSorts = ['name'];

        protected array $allowedFields = ['id', 'name'];
    })->get();

    expect($results[0]->name)->toBe('Bob') // desc
        ->and($results[0])->not->toHaveKey('email')
        ->and($results[0])->toHaveKey('id'); // PK always included
});

test('native filter applies multi-column sort', function () {
    Schema::create('mock_filter_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->integer('score');
        $table->timestamps();
    });

    MockFilterModel::create(['name' => 'Alice', 'score' => 80]);
    MockFilterModel::create(['name' => 'Bob', 'score' => 90]);
    MockFilterModel::create(['name' => 'Charlie', 'score' => 80]);

    $request = new Request(['sort' => 'score,-name']); // score ASC, name DESC

    $results = MockFilterModel::query()->tap(new class($request) extends BaseFilter
    {
        protected array $allowedSorts = ['name', 'score'];
    })->get();

    expect($results[0]->name)->toBe('Charlie')  // score 80, name desc
        ->and($results[1]->name)->toBe('Alice')  // score 80, name desc
        ->and($results[2]->name)->toBe('Bob');    // score 90
});

test('native filter multi-column sort defaults to ASC', function () {
    Schema::create('mock_filter_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->integer('score');
        $table->timestamps();
    });

    MockFilterModel::create(['name' => 'Alice', 'score' => 80]);
    MockFilterModel::create(['name' => 'Bob', 'score' => 90]);
    MockFilterModel::create(['name' => 'Charlie', 'score' => 80]);

    $request = new Request(['sort' => 'score']); // score ASC

    $results = MockFilterModel::query()->tap(new class($request) extends BaseFilter
    {
        protected array $allowedSorts = ['name', 'score'];
    })->get();

    expect($results[0]->name)->toBe('Alice')   // score 80
        ->and($results[1]->name)->toBe('Charlie') // score 80
        ->and($results[2]->name)->toBe('Bob');     // score 90
});

test('native filter applies search across columns', function () {
    Schema::create('mock_filter_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->string('category');
        $table->timestamps();
    });

    MockFilterModel::create(['name' => 'Alice', 'email' => 'alice@example.com', 'category' => 'A']);
    MockFilterModel::create(['name' => 'Bob', 'email' => 'bob@example.com', 'category' => 'B']);

    $request = new Request(['search' => 'bob']);

    $results = MockFilterModel::query()->tap(new class($request) extends BaseFilter
    {
        public function search(Builder $query, string $value): void
        {
            $this->applySearch($query, $value, ['name', 'email']);
        }
    })->get();

    expect($results)->toHaveCount(1)
        ->and($results[0]->name)->toBe('Bob');
});

test('native filter applies search via $searchableColumns', function () {
    Schema::create('mock_filter_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->timestamps();
    });

    MockFilterModel::create(['name' => 'Alice', 'email' => 'alice@example.com']);
    MockFilterModel::create(['name' => 'Bob', 'email' => 'bob@example.com']);

    $request = new Request(['search' => 'bob']);

    $results = MockFilterModel::query()->tap(new class($request) extends BaseFilter
    {
        protected array $searchableColumns = ['name', 'email'];
    })->get();

    expect($results)->toHaveCount(1)
        ->and($results[0]->name)->toBe('Bob');
});

test('native paginate respects per_page cap', function () {
    Schema::create('mock_filter_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->timestamps();
    });

    for ($i = 0; $i < 20; $i++) {
        MockFilterModel::create(['name' => "User {$i}"]);
    }

    $request = new Request(['page' => ['size' => 200, 'number' => 1]]);

    $paginator = MockFilterModel::query()->paginate(
        perPage: (int) $request->integer('page.size', 10),
        page: (int) $request->integer('page.number', 10),
    );

    expect($paginator->perPage())->toBe(200);
});

test('native filter applies array value as WHERE IN', function () {
    Schema::create('mock_filter_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('category');
        $table->timestamps();
    });

    MockFilterModel::create(['name' => 'Alice', 'category' => 'A']);
    MockFilterModel::create(['name' => 'Bob', 'category' => 'B']);
    MockFilterModel::create(['name' => 'Charlie', 'category' => 'C']);

    $request = new Request(['filter' => ['category' => ['A', 'B']]]);

    $results = MockFilterModel::query()->tap(new class($request) extends BaseFilter
    {
        protected array $allowedFilters = ['category'];
    })->get();

    expect($results)->toHaveCount(2)
        ->and($results->pluck('name')->toArray())->toEqualCanonicalizing(['Alice', 'Bob']);
});

test('native filter applies null string as WHERE IS NULL', function () {
    Schema::create('mock_filter_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->nullable();
        $table->timestamps();
    });

    MockFilterModel::create(['name' => 'Alice', 'email' => 'alice@example.com']);
    MockFilterModel::create(['name' => 'Bob', 'email' => null]);

    $request = new Request(['filter' => ['email' => 'null']]);

    $results = MockFilterModel::query()->tap(new class($request) extends BaseFilter
    {
        protected array $allowedFilters = ['email'];
    })->get();

    expect($results)->toHaveCount(1)
        ->and($results[0]->name)->toBe('Bob');
});

test('native filter applies boolean string as exact match', function () {
    Schema::create('mock_filter_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->boolean('is_active')->default(false);
        $table->timestamps();
    });

    MockFilterModel::create(['name' => 'Alice', 'is_active' => true]);
    MockFilterModel::create(['name' => 'Bob', 'is_active' => false]);

    $request = new Request(['filter' => ['is_active' => 'true']]);

    $results = MockFilterModel::query()->tap(new class($request) extends BaseFilter
    {
        protected array $allowedFilters = ['is_active'];
    })->get();

    expect($results)->toHaveCount(1)
        ->and($results[0]->name)->toBe('Alice');
});

test('native filter applies operator prefix eq: as exact match', function () {
    Schema::create('mock_filter_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->timestamps();
    });

    MockFilterModel::create(['name' => 'Alice', 'email' => 'alice@example.com']);
    MockFilterModel::create(['name' => 'Bob', 'email' => 'bob@example.com']);

    $request = new Request(['filter' => ['name' => 'eq:Alice']]);

    $results = MockFilterModel::query()->tap(new class($request) extends BaseFilter
    {
        protected array $allowedFilters = ['name'];
    })->get();

    expect($results)->toHaveCount(1)
        ->and($results[0]->name)->toBe('Alice');
});

test('native filter applies operator prefix neq: as not equal', function () {
    Schema::create('mock_filter_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('category');
        $table->timestamps();
    });

    MockFilterModel::create(['name' => 'Alice', 'category' => 'A']);
    MockFilterModel::create(['name' => 'Bob', 'category' => 'B']);

    $request = new Request(['filter' => ['category' => 'neq:A']]);

    $results = MockFilterModel::query()->tap(new class($request) extends BaseFilter
    {
        protected array $allowedFilters = ['category'];
    })->get();

    expect($results)->toHaveCount(1)
        ->and($results[0]->name)->toBe('Bob');
});

test('native filter applies operator prefix gt: as greater than', function () {
    Schema::create('mock_filter_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->integer('score');
        $table->timestamps();
    });

    MockFilterModel::create(['name' => 'Alice', 'score' => 10]);
    MockFilterModel::create(['name' => 'Bob', 'score' => 20]);

    $request = new Request(['filter' => ['score' => 'gt:10']]);

    $results = MockFilterModel::query()->tap(new class($request) extends BaseFilter
    {
        protected array $allowedFilters = ['score'];
    })->get();

    expect($results)->toHaveCount(1)
        ->and($results[0]->name)->toBe('Bob');
});

test('native filter defaults to ORDER BY created_at DESC, id DESC when sort param missing', function () {
    Schema::create('mock_filter_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->timestamps();
    });

    MockFilterModel::create(['name' => 'Charlie']);
    MockFilterModel::create(['name' => 'Alice']);
    MockFilterModel::create(['name' => 'Bob']);

    $results = MockFilterModel::query()->tap(new class(new Request([])) extends BaseFilter
    {
        protected array $allowedSorts = ['name'];
    })->get();

    expect($results[0]->id)->toBe(3) // ORDER BY created_at DESC, id DESC
        ->and($results[1]->id)->toBe(2)
        ->and($results[2]->id)->toBe(1);
});

test('native filter applies NOT NULL filter', function () {
    Schema::create('mock_filter_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->nullable();
        $table->timestamps();
    });

    MockFilterModel::create(['name' => 'Alice', 'email' => 'alice@example.com']);
    MockFilterModel::create(['name' => 'Bob', 'email' => null]);

    $request = new Request(['filter' => ['email' => '!null']]);

    $results = MockFilterModel::query()->tap(new class($request) extends BaseFilter
    {
        protected array $allowedFilters = ['email'];
    })->get();

    expect($results)->toHaveCount(1)
        ->and($results[0]->name)->toBe('Alice');
});

test('native filter applies like: operator prefix', function () {
    Schema::create('mock_filter_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->timestamps();
    });

    MockFilterModel::create(['name' => 'Alice']);
    MockFilterModel::create(['name' => 'Bob']);
    MockFilterModel::create(['name' => 'Alex']);

    $request = new Request(['filter' => ['name' => 'like:Al%']]);

    $results = MockFilterModel::query()->tap(new class($request) extends BaseFilter
    {
        protected array $allowedFilters = ['name'];
    })->get();

    expect($results)->toHaveCount(2)
        ->and($results->pluck('name')->toArray())->toEqualCanonicalizing(['Alice', 'Alex']);
});

test('native filter applies numeric gt: operator with value cast', function () {
    Schema::create('mock_filter_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->integer('score');
        $table->timestamps();
    });

    MockFilterModel::create(['name' => 'Alice', 'score' => 10]);
    MockFilterModel::create(['name' => 'Bob', 'score' => 20]);
    MockFilterModel::create(['name' => 'Charlie', 'score' => 30]);

    $request = new Request(['filter' => ['score' => 'gt:15.5']]);

    $results = MockFilterModel::query()->tap(new class($request) extends BaseFilter
    {
        protected array $allowedFilters = ['score'];
    })->get();

    expect($results)->toHaveCount(2)
        ->and($results->pluck('name')->toArray())
        ->toEqualCanonicalizing(['Bob', 'Charlie']);
});

test('native filter uses exact match for columns in $exactMatchColumns', function () {
    Schema::create('mock_filter_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('category');
        $table->timestamps();
    });

    MockFilterModel::create(['name' => 'Alice', 'category' => 'admin']);
    MockFilterModel::create(['name' => 'Administrator', 'category' => 'user']);

    $request = new Request(['filter' => ['category' => 'admin']]);

    $results = MockFilterModel::query()->tap(new class($request) extends BaseFilter
    {
        protected array $allowedFilters = ['category'];

        protected array $exactMatchColumns = ['category'];
    })->get();

    expect($results)->toHaveCount(1)
        ->and($results[0]->name)->toBe('Alice');
});

test('native filter applies strategy method for complex filters', function () {
    Schema::create('mock_filter_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->string('category');
        $table->timestamps();
    });

    MockFilterModel::create(['name' => 'Alice', 'email' => 'alice@example.com', 'category' => 'premium']);
    MockFilterModel::create(['name' => 'Bob', 'email' => 'bob@example.com', 'category' => 'basic']);

    $request = new Request(['filter' => ['category' => 'premium']]);

    $results = MockFilterModel::query()->tap(new class($request) extends BaseFilter
    {
        protected array $allowedFilters = ['category'];

        public function category(Builder $query, mixed $value): void
        {
            if ($value === 'premium') {
                $query->where('category', 'premium');
            }
        }
    })->get();

    expect($results)->toHaveCount(1)
        ->and($results[0]->name)->toBe('Alice');
});
