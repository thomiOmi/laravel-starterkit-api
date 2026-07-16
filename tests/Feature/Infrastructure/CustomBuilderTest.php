<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

uses(RefreshDatabase::class);

class MockFilterModel extends Model
{
    protected $table = 'mock_filter_models';

    protected $fillable = ['name', 'email', 'category'];

    protected $hidden = ['email'];
}

test('spatie query builder handles sparse fields via HTTP', function () {
    Schema::create('mock_filter_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->string('category');
        $table->timestamps();
    });

    MockFilterModel::create(['name' => 'Alice', 'email' => 'alice@example.com', 'category' => 'A']);

    Route::get('/test-filter', fn () => response()->json(
        QueryBuilder::for(MockFilterModel::query())
            ->allowedFields('id', 'name', 'email')
            ->get()
    ));

    $response = $this->get('/test-filter?fields[mock_filter_models]=name');

    $data = $response->json();
    expect($data[0])->toHaveKey('name')
        ->and($data[0])->not->toHaveKey('email')
        ->and($data[0])->not->toHaveKey('id');
});

test('spatie query builder applies named filter', function () {
    Schema::create('mock_filter_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->string('category');
        $table->timestamps();
    });

    MockFilterModel::create(['name' => 'Alice', 'email' => 'alice@example.com', 'category' => 'A']);
    MockFilterModel::create(['name' => 'Bob', 'email' => 'bob@example.com', 'category' => 'B']);

    Route::get('/test-filter', fn () => response()->json(
        QueryBuilder::for(MockFilterModel::query())
            ->allowedFilters(AllowedFilter::exact('category'))
            ->get()
    ));

    $response = $this->get('/test-filter?filter[category]=A');

    $data = $response->json();
    expect($data)->toHaveCount(1)
        ->and($data[0]['name'])->toBe('Alice');
});

test('spatie query builder rejects unknown filters', function () {
    Schema::create('mock_filter_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->string('category');
        $table->timestamps();
    });

    MockFilterModel::create(['name' => 'Alice', 'email' => 'alice@example.com', 'category' => 'A']);
    MockFilterModel::create(['name' => 'Bob', 'email' => 'bob@example.com', 'category' => 'B']);

    Route::get('/test-filter', fn () => response()->json(
        QueryBuilder::for(MockFilterModel::query())
            ->allowedFilters(AllowedFilter::exact('category'))
            ->get()
    ));

    $this->get('/test-filter?filter[email]=alice@example.com')->assertStatus(400);
});

test('spatie query builder applies sorting and search', function () {
    Schema::create('mock_filter_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->string('category');
        $table->timestamps();
    });

    MockFilterModel::create(['name' => 'Alice', 'email' => 'alice@example.com', 'category' => 'A']);
    MockFilterModel::create(['name' => 'Bob', 'email' => 'bob@example.com', 'category' => 'B']);

    Route::get('/test-filter', fn () => response()->json(
        QueryBuilder::for(MockFilterModel::query())
            ->allowedFilters(AllowedFilter::partial('name'), AllowedFilter::partial('email'))
            ->allowedSorts(AllowedSort::field('name'))
            ->get()
    ));

    $asc = $this->get('/test-filter?sort=name')->json();
    expect($asc[0]['name'])->toBe('Alice');

    $search = $this->get('/test-filter?filter[name]=bob')->json();
    expect($search)->toHaveCount(1)
        ->and($search[0]['name'])->toBe('Bob');
});
