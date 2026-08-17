<?php

declare(strict_types=1);

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Process;
use Nwidart\Modules\Commands\Make\ModuleMakeCommand;

covers(ModuleMakeCommand::class);

/**
 * @return array<mixed, mixed>
 */
function decodeModuleJson(string $path): array
{
    $json = json_decode(file_get_contents($path) ?: '', true);

    if (! is_array($json)) {
        throw new RuntimeException("Invalid JSON in {$path}");
    }

    return $json;
}

beforeEach(function (): void {
    Process::fake();
});

afterEach(function (): void {
    $files = app('files');

    foreach (['Blog', 'Shop', 'Gadget', 'Fake'] as $module) {
        $files->deleteDirectory(base_path("modules/{$module}"));
    }

    $statusesPath = base_path('modules_statuses.json');

    $statuses = decodeModuleJson($statusesPath);

    unset($statuses['Blog'], $statuses['Shop'], $statuses['Gadget'], $statuses['Fake']);

    $json = json_encode($statuses, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    if (is_string($json)) {
        $files->put($statusesPath, $json);
    }
});

describe('module:make command', function () {
    it('scaffolds a backend-only module following the kit structure', function () {
        artisanCommand($this, 'module:make', ['name' => ['Blog'], '--disabled' => true])
            ->expectsOutputToContain('Module [Blog] created successfully.')
            ->assertSuccessful();

        $modulePath = base_path('modules/Blog');

        foreach ([
            'module.json',
            'composer.json',
            'config/config.php',
            'routes/api.php',
            'app/Providers/BlogServiceProvider.php',
            'app/Providers/RouteServiceProvider.php',
            'app/Providers/EventServiceProvider.php',
            'app/Http/Controllers/BlogController.php',
            'database/factories/.gitkeep',
            'database/migrations/.gitkeep',
            'database/seeders/BlogDatabaseSeeder.php',
            'tests/Feature/.gitkeep',
            'tests/Unit/.gitkeep',
        ] as $file) {
            expect($modulePath.'/'.$file)->toBeFile();
        }

        foreach (['package.json', 'vite.config.js', 'resources', 'routes/web.php'] as $file) {
            expect(file_exists($modulePath.'/'.$file))->toBeFalse();
        }

        $provider = file_get_contents($modulePath.'/app/Providers/BlogServiceProvider.php');
        expect($provider)->toContain('declare(strict_types=1)')
            ->toContain('extends ModuleServiceProvider')
            ->toContain("protected string \$name = 'Blog'")
            ->toContain('EventServiceProvider::class')
            ->toContain('RouteServiceProvider::class');

        $routeProvider = file_get_contents($modulePath.'/app/Providers/RouteServiceProvider.php');
        expect($routeProvider)->toContain('mapApiRoutes')
            ->toContain('mapWebRoutes')
            ->toContain('file_exists')
            ->toContain("->name('v1.')");

        $routes = file_get_contents($modulePath.'/routes/api.php');
        expect($routes)->toContain("'auth:sanctum'")
            ->toContain("->prefix('v1')")
            ->toContain('apiResource')
            ->toContain("->names('blog')");

        $config = file_get_contents($modulePath.'/config/config.php');
        expect($config)->toContain("'name' => 'Blog'");

        $controller = file_get_contents($modulePath.'/app/Http/Controllers/BlogController.php');
        expect($controller)->toContain('final readonly class BlogController extends Controller')
            ->toContain('public function index')
            ->toContain('public function store')
            ->not->toContain('public function create');

        $seeder = file_get_contents($modulePath.'/database/seeders/BlogDatabaseSeeder.php');
        expect($seeder)->toContain('declare(strict_types=1)')
            ->toContain('class BlogDatabaseSeeder extends Seeder');

        $eventProvider = file_get_contents($modulePath.'/app/Providers/EventServiceProvider.php');
        expect($eventProvider)->toContain('declare(strict_types=1)')
            ->toContain('extends ServiceProvider');

        $json = file_get_contents($modulePath.'/module.json');
        expect($json)->toContain('"alias": "blog"')
            ->toContain('Providers\\\\BlogServiceProvider');

        $composer = decodeModuleJson($modulePath.'/composer.json');

        expect($composer['name'])->toBe('thomiomi/blog')
            ->and(Arr::get($composer, 'autoload.psr-4.Modules\\Blog\\'))->toBe('app/')
            ->and(Arr::get($composer, 'autoload.psr-4.Modules\\Blog\\Database\\Factories\\'))->toBe('database/factories/');
    });

    it('creates a plain module without providers, routes and controllers', function () {
        artisanCommand($this, 'module:make', ['name' => ['Gadget'], '--plain' => true, '--disabled' => true])
            ->expectsOutputToContain('Module [Gadget] created successfully.')
            ->assertSuccessful();

        $modulePath = base_path('modules/Gadget');

        expect($modulePath.'/module.json')->toBeFile();

        foreach ([
            'composer.json',
            'config/config.php',
            'routes/api.php',
            'app/Providers/GadgetServiceProvider.php',
            'app/Providers/RouteServiceProvider.php',
            'app/Providers/EventServiceProvider.php',
            'app/Http/Controllers/GadgetController.php',
            'database/seeders/GadgetDatabaseSeeder.php',
        ] as $file) {
            expect(file_exists($modulePath.'/'.$file))->toBeFalse();
        }

        $json = decodeModuleJson($modulePath.'/module.json');
        expect($json['providers'])->toBeEmpty();
    });

    it('can delete a generated module', function () {
        artisanCommand($this, 'module:make', ['name' => ['Shop'], '--disabled' => true])
            ->assertSuccessful();

        expect(base_path('modules/Shop'))->toBeDirectory();

        artisanCommand($this, 'module:delete', ['module' => ['Shop']])
            ->expectsConfirmation('Are you sure you want to run this command?', 'yes')
            ->assertSuccessful();

        expect(base_path('modules/Shop'))->not->toBeDirectory();

        $statuses = decodeModuleJson(base_path('modules_statuses.json'));
        expect($statuses)->not->toHaveKey('Shop');
    });
});
