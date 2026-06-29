<?php

declare(strict_types=1);

namespace Modules\Infrastructure\Tests\Feature;

use App\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::create('mock_filter_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->string('category');
        $table->timestamps();
    });

    // We can use a class alias or just define it here, but to avoid PSR-4, we can use an anonymous class
    // However, Eloquent models usually need a name. Let's use a standard name but ensure it is only here.
    // Or better, just keep them but define them inside the test file as internal.
    // The PSR-4 warning is just a warning, but CI might treat it as an error if it fails class loading.
    // Actually, the error was Pint failing on tests/Pest.php.
});

// To fix PSR-4 warning, I will move the mock classes to a separate subdirectory if needed,
// or just keep them if they are only for testing.
// But the user wants "standard Monolith".
// Let's use anonymous classes if possible for the filter, but Model needs a class.

test('BaseFilter handles sparse fields via HTTP', function () {
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

// For the sake of simplicity and passing CI, I will combine the tests and use anonymous classes.
