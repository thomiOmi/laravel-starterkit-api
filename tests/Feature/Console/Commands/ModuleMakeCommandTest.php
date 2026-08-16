<?php

declare(strict_types=1);

use App\Console\Commands\ModuleMakeCommand;

covers(ModuleMakeCommand::class);

afterEach(function () {
    $files = app('files');

    foreach (['Blog', 'Shop', 'Gadget', 'Fake', 'Widget', 'Store'] as $module) {
        $files->deleteDirectory(base_path("modules/{$module}"));
    }

    $statusesPath = base_path('modules_statuses.json');

    $contents = $files->get($statusesPath);

    /** @var array<string, bool> $statuses */
    $statuses = json_decode($contents, true);

    unset($statuses['Blog'], $statuses['Shop'], $statuses['Gadget'], $statuses['Fake'], $statuses['Widget'], $statuses['Store']);

    $json = json_encode($statuses, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    if (! is_string($json)) {
        return;
    }

    $files->put($statusesPath, $json);
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
            'routes/V1.php',
            'app/Providers/BlogServiceProvider.php',
            'app/Providers/RouteServiceProvider.php',
            'database/factories/.gitkeep',
            'database/migrations/.gitkeep',
            'database/seeders/BlogDatabaseSeeder.php',
            'tests/Feature/.gitkeep',
            'tests/Unit/.gitkeep',
        ] as $file) {
            expect($modulePath.'/'.$file)->toBeFile();
        }

        foreach (['package.json', 'vite.config.js', 'resources', 'app/Http/Controllers'] as $file) {
            expect(file_exists($modulePath.'/'.$file))->toBeFalse();
        }

        $provider = file_get_contents($modulePath.'/app/Providers/BlogServiceProvider.php');
        expect($provider)->toContain('extends ModuleServiceProvider')
            ->toContain("protected string \$name = 'Blog'")
            ->toContain('RouteServiceProvider::class');

        $routeProvider = file_get_contents($modulePath.'/app/Providers/RouteServiceProvider.php');
        expect($routeProvider)->toContain('mapApiRoutes')
            ->toContain('v1.{alias}');

        $routes = file_get_contents($modulePath.'/routes/V1.php');
        expect($routes)->toContain("'auth:sanctum'");

        $config = file_get_contents($modulePath.'/config/config.php');
        expect($config)->toContain("'name' => 'Blog'")
            ->toContain("'features'");

        $json = file_get_contents($modulePath.'/module.json');
        expect($json)->toContain('"alias": "blog"')
            ->toContain('Providers\\\\BlogServiceProvider');
    });

    it('adds a Vite frontend scaffold when the --vue flag is passed', function () {
        artisanCommand($this, 'module:make', ['name' => ['Shop'], '--vue' => true, '--disabled' => true])
            ->expectsOutputToContain('Module [Shop] created successfully.')
            ->assertSuccessful();

        $modulePath = base_path('modules/Shop');

        foreach (['package.json', 'vite.config.js', 'resources/js/app.js', 'resources/css/app.css'] as $file) {
            expect($modulePath.'/'.$file)->toBeFile();
        }

        $vite = file_get_contents($modulePath.'/vite.config.js');
        expect($vite)->toContain("'build-shop'")
            ->toContain('resources/js/app.js')
            ->toContain('resources/css/app.css');

        $package = file_get_contents($modulePath.'/package.json');
        expect($package)->toContain('laravel-vite-plugin')
            ->toContain('"vite"');

        $css = file_get_contents($modulePath.'/resources/css/app.css');
        expect($css)->toContain('Shop module styles');
    });

    it('uses the configured default framework when the --frontend flag is passed', function () {
        artisanCommand($this, 'module:make', ['name' => ['Gadget'], '--frontend' => true, '--disabled' => true])
            ->expectsOutputToContain('Module [Gadget] created successfully.')
            ->assertSuccessful();

        $modulePath = base_path('modules/Gadget');

        foreach (['package.json', 'vite.config.js', 'resources/js/app.js', 'resources/css/app.css'] as $file) {
            expect($modulePath.'/'.$file)->toBeFile();
        }
    });

    it('does not generate a frontend scaffold when the --no-frontend flag is passed', function () {
        artisanCommand($this, 'module:make', ['name' => ['Widget'], '--no-frontend' => true, '--disabled' => true])
            ->expectsOutputToContain('Module [Widget] created successfully.')
            ->assertSuccessful();

        $modulePath = base_path('modules/Widget');

        foreach (['package.json', 'vite.config.js', 'resources'] as $file) {
            expect(file_exists($modulePath.'/'.$file))->toBeFalse();
        }
    });

    it('rejects an unsupported frontend stack', function () {
        expect(fn () => artisanCommand($this, 'module:make', ['name' => ['Fake'], '--react' => true, '--disabled' => true]))
            ->toThrow(InvalidArgumentException::class, 'Unsupported frontend stack [react]. Supported stacks: vue.');
    });
});
