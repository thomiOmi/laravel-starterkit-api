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
        $name = $this->argument('name');

        if (! $name) {
            $name = $this->ask('What is the name of the module? (e.g. Blog)');
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
            'action' => $this->confirm('Create a sample Action?', true),
            'dto' => $this->confirm('Create a sample DTO?', true),
            'request' => $this->confirm('Create a sample Form Request?', true),
            'migration' => $this->confirm('Create Migration?', true),
            'factory' => $this->confirm('Create Factory?', true),
            'seeder' => $this->confirm('Create Seeder?', true),
        ];

        $this->info("Generating module {$name}...");

        $this->createDirectories($modulePath, $options);
        $this->createFiles($name, $modulePath, $options);

        $this->info("Module {$name} created successfully!");
        $this->showSummary($name, $options);
    }

    /**
     * Create module directory structure.
     */
    protected function createDirectories(string $path, array $options): void
    {
        $directories = [
            'Controllers',
            'Models',
            'Providers',
            'Resources',
            'Routes',
        ];

        if ($options['repository']) $directories[] = 'Repositories';
        if ($options['action']) $directories[] = 'Actions';
        if ($options['dto']) $directories[] = 'DTOs';
        if ($options['request']) $directories[] = 'Requests';
        if ($options['migration'] || $options['factory'] || $options['seeder']) {
            $directories[] = 'Database/Migrations';
            if ($options['factory']) $directories[] = 'Database/Factories';
            if ($options['seeder']) $directories[] = 'Database/Seeders';
        }

        foreach ($directories as $dir) {
            File::makeDirectory("{$path}/{$dir}", 0755, true, true);
        }
    }

    /**
     * Create boilerplate files.
     */
    protected function createFiles(string $name, string $path, array $options): void
    {
        // Essential Files
        $this->createFile($path . "/Providers/{$name}ServiceProvider.php", $this->getServiceProviderTemplate($name));
        $this->createFile($path . "/Routes/api.php", $this->getRouteTemplate($name));
        $this->createFile($path . "/Models/{$name}.php", $this->getModelTemplate($name));
        $this->createFile($path . "/Controllers/{$name}Controller.php", $this->getControllerTemplate($name, $options));
        $this->createFile($path . "/Resources/{$name}Resource.php", $this->getResourceTemplate($name));

        // Optional Files
        if ($options['repository']) {
            $this->createFile($path . "/Repositories/{$name}Repository.php", $this->getRepositoryTemplate($name));
        }

        if ($options['dto']) {
            $this->createFile($path . "/DTOs/{$name}DTO.php", $this->getDTOTemplate($name));
        }

        if ($options['request']) {
            $this->createFile($path . "/Requests/{$name}Request.php", $this->getRequestTemplate($name));
        }

        if ($options['migration']) {
            $tableName = Str::snake(Str::plural($name));
            $fileName = date('Y_m_d_His') . "_create_{$tableName}_table.php";
            $this->createFile($path . "/Database/Migrations/{$fileName}", $this->getMigrationTemplate($name, $tableName));
        }
        
        if ($options['seeder']) {
            $this->createFile($path . "/Database/Seeders/{$name}Seeder.php", $this->getSeederTemplate($name));
        }
    }

    protected function createFile(string $path, string $content): void
    {
        File::put($path, $content);
    }

    protected function showSummary(string $name, array $options): void
    {
        $this->table(
            ['Component', 'Status'],
            [
                ['Module Name', $name],
                ['Controller', 'Created'],
                ['Model', 'Created'],
                ['Repository', $options['repository'] ? 'Created' : 'Skipped'],
                ['DTO', $options['dto'] ? 'Created' : 'Skipped'],
                ['Migration', $options['migration'] ? 'Created' : 'Skipped'],
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
    public function boot(): void
    {
        //
    }

    public function register(): void
    {
        //
    }
}
PHP;
    }

    protected function getRouteTemplate(string $name): string
    {
        $slug = Str::kebab(Str::plural($name));
        return <<<PHP
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\\{$name}\\Controllers\\{$name}Controller;

Route::prefix('{$slug}')->group(function () {
    Route::get('/', [{$name}Controller::class, 'index'])->name('index');
});
PHP;
    }

    protected function getModelTemplate(string $name): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$name}\\Models;

use App\Traits\Models\HasDefaultBehavior;
use Illuminate\Database\Eloquent\Model;

class {$name} extends Model
{
    use HasDefaultBehavior;

    protected \$fillable = [];
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

class {$name}Repository extends BaseRepository
{
    public function __construct({$name} \$model)
    {
        parent::__construct(\$model);
    }
}
PHP;
    }

    protected function getControllerTemplate(string $name, array $options): string
    {
        $repoImport = $options['repository'] ? "use Modules\\{$name}\\Repositories\\{$name}Repository;" : "";
        $repoParam = $options['repository'] ? "protected {$name}Repository \$repository" : "";
        $repoBody = $options['repository'] ? "\$data = \$this->repository->paginate();" : "\$data = [];";

        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$name}\\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
{$repoImport}
use Modules\\{$name}\\Resources\\{$name}Resource;

/**
 * @tags {$name}
 */
class {$name}Controller extends Controller
{
    public function __construct({$repoParam})
    {
    }

    public function index(Request \$request): JsonResponse
    {
        {$repoBody}

        return \$this->paginateResponse(\$data, {$name}Resource::class, '{$name} retrieved successfully');
    }
}
PHP;
    }

    protected function getResourceTemplate(string $name): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$name}\\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class {$name}Resource extends JsonResource
{
    public function toArray(Request \$request): array
    {
        return [
            'id' => \$this->id,
            'created_at' => \$this->created_at,
            'updated_at' => \$this->updated_at,
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

use Illuminate\Http\Request;

class {$name}DTO
{
    public function __construct(
        public array \$data
    ) {}

    public static function fromRequest(Request \$request): self
    {
        return new self(\$request->validated());
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

use Illuminate\Foundation\Http\FormRequest;

class {$name}Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
PHP;
    }

    protected function getMigrationTemplate(string $name, string $tableName): string
    {
        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$tableName}', function (Blueprint \$table) {
            \$table->ulid('id')->primary();
            \$table->timestamps();
            \$table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$tableName}');
    }
};
PHP;
    }

    protected function getSeederTemplate(string $name): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Modules\\{$name}\\Database\\Seeders;

use Illuminate\Database\Seeder;

class {$name}Seeder extends Seeder
{
    public function run(): void
    {
        //
    }
}
PHP;
    }
}
