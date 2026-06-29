<?php

declare(strict_types=1);

namespace Modules\Infrastructure\Tests\Feature;

use App\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/**
 * Mock Model for Filter Testing
 */
class MockFilterModel extends Model
{
    protected $table = 'mock_filter_models';

    protected $fillable = ['name', 'email', 'category'];
}

/**
 * Mock Filter Implementation
 */
class MockFilter extends BaseFilter
{
    protected array $allowedFilters = ['category'];

    protected array $allowedSorts = ['name', 'created_at'];

    protected array $allowedFields = ['name', 'email'];

    public function search(Builder $builder, string $value): Builder
    {
        return $this->applySearch($builder, $value, ['name', 'email']);
    }

    protected function category(Builder $builder, string $value): Builder
    {
        return $builder->where('category', $value);
    }
}

beforeEach(function () {
    Schema::create('mock_filter_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->string('category');
        $table->timestamps();
    });

    MockFilterModel::create(['name' => 'Alice', 'email' => 'alice@example.com', 'category' => 'A']);
    MockFilterModel::create(['name' => 'Bob', 'email' => 'bob@example.com', 'category' => 'B']);
    MockFilterModel::create(['name' => 'Charlie', 'email' => 'charlie@example.com', 'category' => 'A']);
});

test('BaseFilter handles sparse fields via HTTP', function () {
    Route::get('/test-filter', function () {
        $filter = new MockFilter(request());
        $query = MockFilterModel::query();

        return response()->json($filter->apply($query)->get());
    });

    // Requesting only 'name' field
    $response = $this->get('/test-filter?fields[mock_filter_models]=name');

    $data = $response->json();
    expect($data[0])->toHaveKey('name')
        ->and($data[0])->not->toHaveKey('email')
        ->and($data[0])->toHaveKey('id'); // Primary key is always included
});

test('BaseFilter handles global search via HTTP', function () {
    Route::get('/test-filter', function () {
        $filter = new MockFilter(request());
        $query = MockFilterModel::query();

        return response()->json($filter->apply($query)->get());
    });

    // Searching for 'Bob'
    $response = $this->get('/test-filter?search=Bob');

    $data = $response->json();
    expect($data)->toHaveCount(1)
        ->and($data[0]['name'])->toBe('Bob');
});

test('BaseFilter handles named filters via HTTP', function () {
    Route::get('/test-filter', function () {
        $filter = new MockFilter(request());
        $query = MockFilterModel::query();

        return response()->json($filter->apply($query)->get());
    });

    // Filtering by category 'A'
    $response = $this->get('/test-filter?filter[category]=A');

    $data = $response->json();
    expect($data)->toHaveCount(2)
        ->and(collect($data)->pluck('name')->toArray())->toContain('Alice', 'Charlie');
});

test('BaseFilter handles multi-column sorting via HTTP', function () {
    Route::get('/test-filter', function () {
        $filter = new MockFilter(request());
        $query = MockFilterModel::query();

        return response()->json($filter->apply($query)->get());
    });

    // Sorting by name descending
    $response = $this->get('/test-filter?sort=-name');

    $data = $response->json();
    expect($data[0]['name'])->toBe('Charlie')
        ->and($data[1]['name'])->toBe('Bob')
        ->and($data[2]['name'])->toBe('Alice');
});
