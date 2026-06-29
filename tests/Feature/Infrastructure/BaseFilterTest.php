<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure;

use App\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

test('BaseFilter handles sparse fields via HTTP', function () {
    Schema::create('mock_filter_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->string('category');
        $table->timestamps();
    });

    $model = new class extends Model
    {
        protected $table = 'mock_filter_models';

        protected $fillable = ['name', 'email', 'category'];
    };

    $model::create(['name' => 'Alice', 'email' => 'alice@example.com', 'category' => 'A']);

    Route::get('/test-filter', function () use ($model) {
        $filter = new class(request()) extends BaseFilter
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
        };

        return response()->json($filter->apply($model::query())->get());
    });

    $response = $this->get('/test-filter?fields[mock_filter_models]=name');

    $data = $response->json();
    expect($data[0])->toHaveKey('name')
        ->and($data[0])->not->toHaveKey('email')
        ->and($data[0])->toHaveKey('id');
});
