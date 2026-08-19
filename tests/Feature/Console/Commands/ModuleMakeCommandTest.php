<?php

declare(strict_types=1);

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Process;
use Nwidart\Modules\Commands\Make\ModuleMakeCommand;

covers(ModuleMakeCommand::class);

beforeEach(function (): void {
    Process::fake();

    $root = base_path('tests/Fixtures/module-make');
    $files = app('files');

    $files->makeDirectory($root, 0755, true);
    $files->put($root.'/statuses.json', '{}');

    config()->set('modules.paths.modules', $root.'/modules');
    config()->set('modules.activators.file.statuses-file', $root.'/statuses.json');
    app()->forgetInstance('modules');
});

afterEach(function (): void {
    app('files')->deleteDirectory(base_path('tests/Fixtures/module-make'));
});

describe('module:make command', function () {
    it('scaffolds a backend-only module following the kit structure', function () {
        artisanCommand($this, 'module:make', ['name' => ['Blog'], '--disabled' => true])
            ->expectsOutputToContain('Module [Blog] created successfully.')
            ->assertSuccessful();

        $modulePath = base_path('tests/Fixtures/module-make/modules/Blog');

        foreach ([
            'module.json',
            'composer.json',
            'config/config.php',
            'routes/V1.php',
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
            ->toContain('RouteServiceProvider::class')
            ->toContain('protected array $commands = [];');

        $routeProvider = file_get_contents($modulePath.'/app/Providers/RouteServiceProvider.php');
        expect($routeProvider)->toContain('mapApiRoutes')
            ->toContain('mapWebRoutes')
            ->toContain('file_exists')
            ->toContain('apiroute.supported_versions')
            ->toContain("->name('api.'.strtolower(\$version).'.'.\$this->nameLower.'.')");

        $routes = file_get_contents($modulePath.'/routes/V1.php');
        expect($routes)->toContain("'auth:sanctum'")
            ->toContain('apiResource')
            ->not->toContain("->prefix('v1')")
            ->not->toContain("->names('blog')");

        $config = file_get_contents($modulePath.'/config/config.php');
        expect($config)->toContain("'name' => 'Blog'");

        $controller = file_get_contents($modulePath.'/app/Http/Controllers/BlogController.php');
        expect($controller)->toContain('final readonly class BlogController extends Controller')
            ->toContain('use App\Http\Responses\SuccessResponse;')
            ->toContain('return new SuccessResponse(data: []);')
            ->toContain('status: Response::HTTP_CREATED')
            ->toContain('public function index')
            ->toContain('public function store')
            ->not->toContain('public function create')
            ->not->toContain('JsonResponse')
            ->not->toContain('response()->json');

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

        $modulePath = base_path('tests/Fixtures/module-make/modules/Gadget');

        expect($modulePath.'/module.json')->toBeFile();

        foreach ([
            'composer.json',
            'config/config.php',
            'routes/V1.php',
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

        expect(base_path('tests/Fixtures/module-make/modules/Shop'))->toBeDirectory();

        artisanCommand($this, 'module:delete', ['module' => ['Shop']])
            ->expectsConfirmation('Are you sure you want to run this command?', 'yes')
            ->assertSuccessful();

        expect(base_path('tests/Fixtures/module-make/modules/Shop'))->not->toBeDirectory();

        $statuses = decodeModuleJson(base_path('tests/Fixtures/module-make/statuses.json'));
        expect($statuses)->not->toHaveKey('Shop');
    });
});
