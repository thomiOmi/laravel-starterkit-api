<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure;

use App\Query\Builder;
use App\Query\FilterConfigurable;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

#[UseEloquentBuilder(Builder::class)]
class MockFilterModel extends Model implements FilterConfigurable
{
    protected $table = 'mock_filter_models';

    protected $fillable = ['name', 'email', 'category'];

    protected array $allowedFilters = ['category', 'status'];

    protected array $allowedSorts = ['name', 'created_at'];

    protected array $allowedFields = ['name', 'email'];

    protected array $searchable = ['name', 'email'];

    public function getAllowedFilters(): array
    {
        return $this->allowedFilters;
    }

    public function getAllowedSorts(): array
    {
        return $this->allowedSorts;
    }

    public function getAllowedFields(): array
    {
        return $this->allowedFields;
    }

    public function getSearchable(): array
    {
        return $this->searchable;
    }

    public function getFieldsKey(): ?string
    {
        return $this->fieldsKey;
    }

    protected function scopeFilterCategory(Builder $query, string $value): void
    {
        $query->where('category', $value);
    }

    protected function scopeFilterStatus(Builder $query, string $value): void
    {
        if ($value === 'active') {
            $query->whereNotNull('email');
        }
    }
}

test('custom builder handles sparse fields via HTTP', function () {
    Schema::create('mock_filter_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->string('category');
        $table->timestamps();
    });

    MockFilterModel::create(['name' => 'Alice', 'email' => 'alice@example.com', 'category' => 'A']);

    Route::get('/test-filter', fn () => response()->json(MockFilterModel::query()->filter(request())->get()));

    $response = $this->get('/test-filter?fields[mock_filter_models]=name');

    $data = $response->json();
    expect($data[0])->toHaveKey('name')
        ->and($data[0])->not->toHaveKey('email')
        ->and($data[0])->toHaveKey('id');
});

test('custom builder applies named filter to local scope', function () {
    Schema::create('mock_filter_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->string('category');
        $table->timestamps();
    });

    MockFilterModel::create(['name' => 'Alice', 'email' => 'alice@example.com', 'category' => 'A']);
    MockFilterModel::create(['name' => 'Bob', 'email' => 'bob@example.com', 'category' => 'B']);

    Route::get('/test-filter', fn () => response()->json(MockFilterModel::query()->filter(request())->get()));

    $response = $this->get('/test-filter?filter[category]=A');

    $data = $response->json();
    expect($data)->toHaveCount(1)
        ->and($data[0]['name'])->toBe('Alice');
});

test('custom builder ignores filter keys not in allowed list', function () {
    Schema::create('mock_filter_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->string('category');
        $table->timestamps();
    });

    MockFilterModel::create(['name' => 'Alice', 'email' => 'alice@example.com', 'category' => 'A']);
    MockFilterModel::create(['name' => 'Bob', 'email' => 'bob@example.com', 'category' => 'B']);

    Route::get('/test-filter', fn () => response()->json(MockFilterModel::query()->filter(request())->get()));

    $response = $this->get('/test-filter?filter[email]=alice@example.com');

    $data = $response->json();
    expect($data)->toHaveCount(2);
});

test('custom builder applies sorting and search', function () {
    Schema::create('mock_filter_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->string('category');
        $table->timestamps();
    });

    MockFilterModel::create(['name' => 'Alice', 'email' => 'alice@example.com', 'category' => 'A']);
    MockFilterModel::create(['name' => 'Bob', 'email' => 'bob@example.com', 'category' => 'B']);

    Route::get('/test-filter', fn () => response()->json(MockFilterModel::query()->filter(request())->get()));

    $asc = $this->get('/test-filter?sort=name')->json();
    expect($asc[0]['name'])->toBe('Alice');

    $search = $this->get('/test-filter?search=bob')->json();
    expect($search)->toHaveCount(1)
        ->and($search[0]['name'])->toBe('Bob');
});
