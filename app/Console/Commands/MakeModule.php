<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeModule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:module {name? : The name of the module} {--force : Overwrite existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new module with an interactive and advanced structure';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $nameArgument = $this->argument('name');
        $name = is_string($nameArgument) ? $nameArgument : '';

        if ($name === '') {
            $askedName = $this->ask('What is the name of the module? (e.g. Blog)');
            $name = is_string($askedName) ? $askedName : '';
        }

        if (! $name) {
            $this->error('Module name is required!');

            return;
        }

        $name = Str::studly($name);
        $modulePath = base_path("modules/{$name}");

        if (File::exists($modulePath) && ! $this->option('force')) {
            if (! $this->confirm("Module {$name} already exists. Do you want to overwrite it?", false)) {
                $this->info('Aborted.');

                return;
            }
        }

        // Interactive choices
        $options = [
            'repository' => $this->confirm('Create Repository?', true),
            'action' => $this->confirm('Create CRUD Actions?', true),
            'dto' => $this->confirm('Create DTO?', true),
            'request' => $this->confirm('Create Form Request?', true),
            'filter' => $this->confirm('Create Query Filter?', true),
            'migration' => $this->confirm('Create Migration?', true),
            'factory' => $this->confirm('Create Factory?', true),
            'seeder' => $this->confirm('Create Seeder?', true),
            'resource' => $this->confirm('Create Resource?', true),
        ];

        $this->info("Generating module {$name}...");

        $this->createDirectories($modulePath, $options);
        $this->createFiles($name, $modulePath, $options);

        $this->info("Module {$name} created successfully!");
        $this->showSummary($name, $options);
    }

    /**
     * Create module directory structure.
     *
     * @param  array<string, bool>  $options
     */
    protected function createDirectories(string $path, array $options): void
    {
        $directories = [
            'Controllers/V1',
            'Models',
            'Providers',
            'Resources',
            'Routes',
            'Tests/Feature',
        ];

        if ($options['repository']) {
            $directories[] = 'Repositories';
        }
        if ($options['action']) {
            $directories[] = 'Actions';
        }
        if ($options['dto']) {
            $directories[] = 'DTOs';
        }
        if ($options['request']) {
            $directories[] = 'Requests';
        }
        if ($options['filter']) {
            $directories[] = 'Filters';
        }

        if ($options['migration'] || $options['factory'] || $options['seeder']) {
            $directories[] = 'Database/Migrations';
            if ($options['factory']) {
                $directories[] = 'Database/Factories';
            }
            if ($options['seeder']) {
                $directories[] = 'Database/Seeders';
            }
        }

        foreach ($directories as $dir) {
            File::makeDirectory("{$path}/{$dir}", 0755, true, true);
        }
    }

    /**
     * Create boilerplate files.
     *
     * @param  array<string, bool>  $options
     */
    protected function createFiles(string $name, string $path, array $options): void
    {
        // Essential Files
        $this->createFile($path."/Providers/{$name}ServiceProvider.php", $this->getServiceProviderTemplate($name));
        $this->createFile($path.'/Routes/v1.php', $this->getRouteTemplate($name, $options));
        $this->createFile($path."/Models/{$name}.php", $this->getModelTemplate($name));
        $this->createFile($path."/Controllers/V1/{$name}Controller.php", $this->getControllerTemplate($name, $options));
        $this->createFile($path."/Resources/{$name}Resource.php", $this->getResourceTemplate($name));

        // Optional Files
        if ($options['repository']) {
            $this->createFile($path."/Repositories/{$name}Repository.php", $this->getRepositoryTemplate($name));
        }

        if ($options['action']) {
            $this->createFile($path."/Actions/Create{$name}Action.php", $this->getCreateActionTemplate($name, $options));
            $this->createFile($path."/Actions/Update{$name}Action.php", $this->getUpdateActionTemplate($name, $options));
            $this->createFile($path."/Actions/Delete{$name}Action.php", $this->getDeleteActionTemplate($name, $options));
        }

        if ($options['dto']) {
            $this->createFile($path."/DTOs/{$name}DTO.php", $this->getDTOTemplate($name));
        }

        if ($options['request']) {
            $this->createFile($path."/Requests/{$name}Request.php", $this->getRequestTemplate($name));
        }

        if ($options['filter']) {
            $this->createFile($path."/Filters/{$name}Filter.php", $this->getFilterTemplate($name));
        }

        if ($options['migration']) {
            $this->generateMigration($name, $path.'/Database/Migrations');
        }

        if ($options['factory']) {
            $this->createFile($path."/Database/Factories/{$name}Factory.php", $this->getFactoryTemplate($name));
        }

        if ($options['seeder']) {
            $this->createFile($path."/Database/Seeders/{$name}Seeder.php", $this->getSeederTemplate($name));
        }
    }

    protected function createFile(string $path, string $content): void
    {
        File::put($path, $content);
    }

    /**
     * @param  array<string, bool>  $options
     */
    protected function showSummary(string $name, array $options): void
    {
        $this->table(
            ['Component', 'Status'],
            [
                ['Module Name', $name],
                ['Controller (V1)', 'Created'],
                ['Model', 'Created'],
                ['Repository', $options['repository'] ? 'Created' : 'Skipped'],
                ['Actions (CRUD)', $options['action'] ? 'Created' : 'Skipped'],
                ['DTO', $options['dto'] ? 'Created' : 'Skipped'],
                ['Filter', $options['filter'] ? 'Created' : 'Skipped'],
                ['Migration', $options['migration'] ? 'Created' : 'Skipped'],
                ['Factory', $options['factory'] ? 'Created' : 'Skipped'],
                ['Seeder', $options['seeder'] ? 'Created' : 'Skipped'],
            ]
        );
    }

    // Templates
    protected function getServiceProviderTemplate(string $name): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$name}\\Providers;

use Illuminate\Support\ServiceProvider;

class {$name}ServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
PHP;
    }

    /** @param array<string, bool> $options */
    protected function getRouteTemplate(string $name, array $options): string
    {
        $slug = Str::kebab(Str::plural($name));
        $routes = "    Route::get('/', [{$name}Controller::class, 'index'])->name('index');\n";

        if ($options['request']) {
            $routes .= "    Route::post('/', [{$name}Controller::class, 'store'])->name('store');\n";
        }

        $routes .= "    Route::get('/{id}', [{$name}Controller::class, 'show'])->name('show');\n";

        if ($options['request']) {
            $routes .= "    Route::put('/{id}', [{$name}Controller::class, 'update'])->name('update');\n";
        }

        if ($options['action']) {
            $routes .= "    Route::delete('/{id}', [{$name}Controller::class, 'destroy'])->name('destroy');\n";
        }

        if ($options['repository']) {
            $routes .= "    Route::post('/bulk', [{$name}Controller::class, 'bulkAction'])->name('bulk');\n";
        }

        return <<<PHP
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\\{$name}\\Controllers\\V1\\{$name}Controller;

Route::prefix('{$slug}')->name('{$slug}.')->group(function () {
{$routes}});
PHP;
    }

    protected function getModelTemplate(string $name): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$name}\\Models;

use App\Traits\Models\HasDefaultBehavior;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\\{$name}\\Database\\Factories\\{$name}Factory;

/**
 * @property string \$id
 * @property \Illuminate\Support\Carbon|null \$created_at
 * @property \Illuminate\Support\Carbon|null \$updated_at
 * @property \Illuminate\Support\Carbon|null \$deleted_at
 */
#[Fillable(['name'])]
class {$name} extends Model
{
    /** @use HasFactory<{$name}Factory> */
    use HasDefaultBehavior, HasFactory, SoftDeletes;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): {$name}Factory
    {
        return {$name}Factory::new();
    }
}
PHP;
    }

    protected function getRepositoryTemplate(string $name): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$name}\\Repositories;

use App\Repositories\BaseRepository;
use Modules\\{$name}\\Models\\{$name};

/**
 * @template T of {$name}
 * @extends BaseRepository<T>
 */
class {$name}Repository extends BaseRepository
{
    /**
     * Create a new repository instance.
     */
    public function __construct({$name} \$model)
    {
        parent::__construct(\$model);
    }

    /**
     * Get the columns that can be filtered.
     *
     * @return array<int, string>
     */
    protected function getFilterableColumns(): array
    {
        return ['name'];
    }

    /**
     * Get the columns that can be sorted.
     *
     * @return array<int, string>
     */
    protected function getSortableColumns(): array
    {
        return ['name', 'created_at'];
    }
}
PHP;
    }

    /** @param array<string, bool> $options */
    protected function getControllerTemplate(string $name, array $options): string
    {
        $repoImport = $options['repository'] ? "use Modules\\{$name}\\Repositories\\{$name}Repository;" : '';
        $filterImport = $options['filter'] ? "use Modules\\{$name}\\Filters\\{$name}Filter;" : '';
        $dtoImport = $options['dto'] ? "use Modules\\{$name}\\DTOs\\{$name}DTO;" : '';
        $requestImport = $options['request'] ? "use Modules\\{$name}\\Requests\\{$name}Request;" : '';
        $resourceImport = "use Modules\\{$name}\\Resources\\{$name}Resource;";
        $bulkImport = $options['repository'] ? "use App\Http\Requests\BulkActionRequest;" : '';

        $actionImports = '';
        if ($options['action']) {
            $actionImports .= "use Modules\\{$name}\\Actions\\Create{$name}Action;\n";
            $actionImports .= "use Modules\\{$name}\\Actions\\Update{$name}Action;\n";
            $actionImports .= "use Modules\\{$name}\\Actions\\Delete{$name}Action;\n";
        }

        $constructorParams = [];
        if ($options['repository']) {
            $constructorParams[] = "protected {$name}Repository \$repository";
        }
        $constructor = '    public function __construct('.implode(', ', $constructorParams).') {}';

        $indexParams = ['Request $request'];
        if ($options['filter']) {
            $indexParams[] = "{$name}Filter \$filter";
        }
        $indexParamsStr = implode(', ', $indexParams);

        if ($options['repository']) {
            $indexBody = $options['filter']
                ? "        \$items = \$this->repository->applyFilter(\$filter)->paginate(\$request->integer('per_page', 10));"
                : "        \$items = \$this->repository->paginate(\$request->integer('per_page', 10));";
        } else {
            $indexBody = "        \$items = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);";
        }

        $storeMethod = '';
        if ($options['request'] && $options['action']) {
            $storeMethod = <<<PHP
    /**
     * Store a newly created resource in storage.
     */
    public function store({$name}Request \$request, Create{$name}Action \$action): JsonResponse
    {
        \$dto = {$name}DTO::fromRequest(\$request);
        \$item = \$action->execute(\$dto);

        return \$this->successResponse(new {$name}Resource(\$item), '{$name} created successfully', 201);
    }

PHP;
        }

        $showMethod = '';
        if ($options['repository']) {
            $showMethod = <<<PHP
    /**
     * Display the specified resource.
     */
    public function show(string|int \$id): JsonResponse
    {
        \$item = \$this->repository->findById(\$id);

        return \$this->successResponse(new {$name}Resource(\$item), '{$name} retrieved successfully');
    }

PHP;
        }

        $updateMethod = '';
        if ($options['request'] && $options['action']) {
            $updateMethod = <<<PHP
    /**
     * Update the specified resource in storage.
     */
    public function update({$name}Request \$request, string|int \$id, Update{$name}Action \$action): JsonResponse
    {
        \$dto = {$name}DTO::fromRequest(\$request);
        \$item = \$action->execute(\$id, \$dto);

        return \$this->successResponse(new {$name}Resource(\$item), '{$name} updated successfully');
    }

PHP;
        }

        $destroyMethod = '';
        if ($options['action']) {
            $destroyMethod = <<<PHP
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string|int \$id, Delete{$name}Action \$action): JsonResponse
    {
        \$action->execute(\$id);

        return \$this->successResponse(null, '{$name} deleted successfully');
    }

PHP;
        }

        $bulkMethod = '';
        if ($options['repository']) {
            $bulkMethod = <<<'PHP'
    /**
     * Perform bulk action.
     */
    public function bulkAction(BulkActionRequest $request): JsonResponse
    {
        /** @var array{ids: array<int, string|int>, action: string} $validated */
        $validated = $request->validated();

        $count = $this->repository->bulk($validated['ids'], $validated['action']);

        return $this->successResponse(['count' => $count], "Items {$validated['action']} successfully");
    }
PHP;
        }

        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$name}\\Controllers\\V1;

use App\Http\Controllers\Controller;
{$bulkImport}
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
{$repoImport}
{$filterImport}
{$dtoImport}
{$requestImport}
{$resourceImport}
{$actionImports}

/**
 * @tags {$name}
 */
class {$name}Controller extends Controller
{
    /**
     * Create a new controller instance.
     */
{$constructor}

    /**
     * Display a paginated listing of the resource.
     */
    #[QueryParameter(name: 'page', type: 'integer', default: 1)]
    #[QueryParameter(name: 'per_page', type: 'integer', default: 10)]
    #[QueryParameter(name: 'search', type: 'string')]
    #[QueryParameter(name: 'sort_by', type: 'string', default: 'created_at')]
    #[QueryParameter(name: 'sort_direction', type: 'string', default: 'desc')]
    public function index({$indexParamsStr}): JsonResponse
    {
{$indexBody}

        return \$this->paginateResponse(\$items, {$name}Resource::class, '{$name} retrieved successfully');
    }

{$storeMethod}{$showMethod}{$updateMethod}{$destroyMethod}{$bulkMethod}
}
PHP;
    }

    protected function getResourceTemplate(string $name): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$name}\\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @mixin \Modules\\{$name}\\Models\\{$name}
 */
class {$name}Resource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request \$request): array
    {
        return [
            /**
             * @example "01hpv4n8f8xrd2m8q0e4x8j9v1"
             */
            'id' => \$this->id,
            'name' => \$this->name,
            'created_at' => \$this->formatDate(\$this->created_at),
            'updated_at' => \$this->formatDate(\$this->updated_at),
        ];
    }
}
PHP;
    }

    protected function getDTOTemplate(string $name): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$name}\\DTOs;

use Illuminate\Foundation\Http\FormRequest;

readonly class {$name}DTO
{
    /**
     * Create a new DTO instance.
     */
    public function __construct(
        public string \$name
    ) {}

    /**
     * Create a DTO from a request.
     */
    public static function fromRequest(FormRequest \$request): self
    {
        /** @var array{name: string} \$validated */
        \$validated = \$request->validated();

        return new self(\$validated['name']);
    }

    /**
     * Convert DTO to array for model persistence.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => \$this->name,
        ];
    }
}
PHP;
    }

    protected function getRequestTemplate(string $name): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$name}\\Requests;

use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Foundation\Http\FormRequest;

#[BodyParameter(name: 'name', description: 'The name of the resource.', required: true, example: 'Example Name')]
class {$name}Request extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
PHP;
    }

    protected function getMigrationTemplate(string $name, string $tableName): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('{$tableName}', function (Blueprint \$table) {
            \$table->ulid('id')->primary();
            \$table->string('name');
            \$table->timestamps();
            \$table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('{$tableName}');
    }
};
PHP;
    }

    protected function getFactoryTemplate(string $name): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$name}\\Database\\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\\{$name}\\Models\\{$name};

/**
 * @extends Factory<{$name}>
 */
class {$name}Factory extends Factory
{
    protected \$model = {$name}::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => \$this->faker->word(),
        ];
    }
}
PHP;
    }

    protected function getSeederTemplate(string $name): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$name}\\Database\\Seeders;

use Illuminate\Database\Seeder;
use Modules\\{$name}\\Models\\{$name};

class {$name}Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        {$name}::factory()->count(10)->create();
    }
}
PHP;
    }

    protected function getFilterTemplate(string $name): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$name}\\Filters;

use App\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;
use Modules\\{$name}\\Models\\{$name};

/**
 * @template T of {$name}
 * @extends BaseFilter<T>
 */
class {$name}Filter extends BaseFilter
{
    /**
     * Filter by search term.
     */
    public function search(string \$value): Builder
    {
        return \$this->builder->where('name', 'like', "%\$value%");
    }
}
PHP;
    }

    /** @param array<string, bool> $options */
    protected function getCreateActionTemplate(string $name, array $options): string
    {
        $dtoImport = $options['dto'] ? "use Modules\\{$name}\\DTOs\\{$name}DTO;" : '';

        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$name}\\Actions;

{$dtoImport}
use Modules\\{$name}\\Models\\{$name};
use Modules\\{$name}\\Repositories\\{$name}Repository;

class Create{$name}Action
{
    public function __construct(protected {$name}Repository \$repository) {}

    public function execute({$name}DTO \$dto): {$name}
    {
        return \$this->repository->create(\$dto->toArray());
    }
}
PHP;
    }

    /** @param array<string, bool> $options */
    protected function getUpdateActionTemplate(string $name, array $options): string
    {
        $dtoImport = $options['dto'] ? "use Modules\\{$name}\\DTOs\\{$name}DTO;" : '';

        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$name}\\Actions;

{$dtoImport}
use Modules\\{$name}\\Models\\{$name};
use Modules\\{$name}\\Repositories\\{$name}Repository;

class Update{$name}Action
{
    public function __construct(protected {$name}Repository \$repository) {}

    public function execute(string|int \$id, {$name}DTO \$dto): {$name}
    {
        return \$this->repository->update(\$id, \$dto->toArray());
    }
}
PHP;
    }

    /** @param array<string, bool> $options */
    protected function getDeleteActionTemplate(string $name, array $options): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$name}\\Actions;

use Modules\\{$name}\\Repositories\\{$name}Repository;

class Delete{$name}Action
{
    public function __construct(protected {$name}Repository \$repository) {}

    public function execute(string|int \$id): void
    {
        \$this->repository->delete(\$id);
    }
}
PHP;
    }

    protected function generateMigration(string $name, string $path): void
    {
        $tableName = Str::snake(Str::plural($name));
        $fileName = date('Y_m_d_His')."_create_{$tableName}_table.php";
        $fullPath = "{$path}/{$fileName}";

        if ($this->option('force')) {
            $files = File::files($path);
            foreach ($files as $file) {
                if (str_contains($file->getFilename(), "_create_{$tableName}_table.php")) {
                    File::delete($file->getPathname());
                    $this->warn('Deleted old migration: '.$file->getFilename());
                }
            }
        }

        $this->createFile($fullPath, $this->getMigrationTemplate($name, $tableName));
    }
}
