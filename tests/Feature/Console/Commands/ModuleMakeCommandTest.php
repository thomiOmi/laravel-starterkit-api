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

    it('generates Vue Inertia pages when the --vue flag is passed', function () {
        artisanCommand($this, 'module:make', ['name' => ['Shop'], '--vue' => true, '--disabled' => true])
            ->expectsOutputToContain('Module [Shop] created successfully.')
            ->assertSuccessful();

        $modulePath = base_path('modules/Shop');

        foreach (['Index', 'Create', 'Show', 'Edit'] as $page) {
            expect($modulePath."/resources/js/Pages/{$page}.vue")->toBeFile();
        }

        $index = file_get_contents($modulePath.'/resources/js/Pages/Index.vue');
        expect($index)->toContain('@inertiajs/vue3')
            ->toContain('Shop - Index');

        foreach (['package.json', 'vite.config.js', 'resources/css'] as $file) {
            expect(file_exists($modulePath.'/'.$file))->toBeFalse();
        }
    });

    it('generates Svelte Inertia pages when the --svelte flag is passed', function () {
        artisanCommand($this, 'module:make', ['name' => ['Gadget'], '--svelte' => true, '--disabled' => true])
            ->expectsOutputToContain('Module [Gadget] created successfully.')
            ->assertSuccessful();

        $modulePath = base_path('modules/Gadget');

        foreach (['Index', 'Create', 'Show', 'Edit'] as $page) {
            expect($modulePath."/resources/js/Pages/{$page}.svelte")->toBeFile();
        }

        $index = file_get_contents($modulePath.'/resources/js/Pages/Index.svelte');
        expect($index)->toContain('@inertiajs/svelte')
            ->toContain('Gadget - Index');
    });

    it('generates React Inertia pages when the --react flag is passed', function () {
        artisanCommand($this, 'module:make', ['name' => ['Fake'], '--react' => true, '--disabled' => true])
            ->expectsOutputToContain('Module [Fake] created successfully.')
            ->assertSuccessful();

        $modulePath = base_path('modules/Fake');

        foreach (['Index', 'Create', 'Show', 'Edit'] as $page) {
            expect($modulePath."/resources/js/Pages/{$page}.jsx")->toBeFile();
        }

        $index = file_get_contents($modulePath.'/resources/js/Pages/Index.jsx');
        expect($index)->toContain('@inertiajs/react')
            ->toContain('Fake - Index');
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
});
