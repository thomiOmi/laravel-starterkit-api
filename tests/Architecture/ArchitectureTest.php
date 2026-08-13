<?php

declare(strict_types=1);

use App\Builders\BaseQueryBuilder;
use App\Http\Requests\PaginationRequest;
use FilesystemIterator;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;
use PHPUnit\Framework\Assert;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/*
|--------------------------------------------------------------------------
| Presets
|--------------------------------------------------------------------------
*/

arch()->preset()->laravel()
    ->ignoring(['Modules', 'Tests', 'bootstrap/app.php']);

/*
|--------------------------------------------------------------------------
| Strictness
|--------------------------------------------------------------------------
*/

arch('app uses strict types')
    ->expect(['App', 'Modules', 'Tests'])
    ->toUseStrictTypes();

arch('app uses strict equality')
    ->expect(['App', 'Modules', 'Tests'])
    ->toUseStrictEquality();

/*
|--------------------------------------------------------------------------
| Coding standards
|--------------------------------------------------------------------------
*/

arch('avoid insecure functions')
    ->expect(['md5', 'uniqid', 'tempnam', 'str_shuffle', 'shuffle', 'array_rand'])
    ->not->toBeUsed();

arch('avoid sha1 and parse_str outside app')
    ->expect(['sha1', 'parse_str'])
    ->not->toBeUsedIn(['Modules', 'Database', 'Routes', 'Bootstrap', 'Tests']);

arch('avoid insecure random functions')
    ->expect(['rand', 'mt_rand'])
    ->not->toBeUsed();

arch('avoid code execution functions')
    ->expect(['eval', 'exec', 'shell_exec', 'system', 'passthru', 'create_function', 'unserialize', 'dl', 'assert'])
    ->not->toBeUsed();

arch('avoid variable injection functions')
    ->expect(['extract', 'mb_parse_str'])
    ->not->toBeUsed();

arch('avoid deprecated PHP functions')
    ->expect(['ereg', 'eregi', 'mysql_connect', 'mysql_pconnect', 'mysql_query', 'mysql_select_db', 'mysql_fetch_array', 'mysql_fetch_assoc', 'mysql_fetch_object', 'mysql_fetch_row', 'mysql_num_rows', 'mysql_affected_rows', 'mysql_free_result', 'mysql_insert_id', 'mysql_error', 'mysql_real_escape_string'])
    ->not->toBeUsed();

arch('avoid debug tracing in production')
    ->expect(['debug_backtrace', 'debug_print_backtrace', 'debug_zval_dump', 'phpinfo'])
    ->not->toBeUsed();

arch('avoid remaining debug functions')
    ->expect(['var_dump', 'print_r'])
    ->not->toBeUsed();

arch('avoid sleep in application code')
    ->expect(['sleep', 'usleep'])
    ->not->toBeUsed();

arch('env should only be used in config')
    ->expect('env')
    ->not->toBeUsedIn(['App', 'Modules', 'Database', 'Routes', 'Bootstrap', 'Tests']);

arch('tests should not use PHPUnit assertions')
    ->expect('Tests')
    ->not->toUse(Assert::class);

/*
|--------------------------------------------------------------------------
| App shared layer
|--------------------------------------------------------------------------
*/

arch('app contracts should be interfaces')
    ->expect('App\Contracts')
    ->toBeInterfaces();

arch('app http responses should implement Responsable')
    ->expect('App\Http\Responses')
    ->classes()
    ->toImplement(Responsable::class);

/*
|--------------------------------------------------------------------------
| Module core structure
|--------------------------------------------------------------------------
*/

arch('module actions are final and readonly')
    ->expect('Modules\*\Actions')
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();

arch('module controllers are final and readonly')
    ->expect('Modules\*\Controllers')
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();

arch('module payloads are final and readonly')
    ->expect('Modules\*\Payloads')
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();

arch('module services are final and readonly')
    ->expect('Modules\*\Services')
    ->classes()
    ->toBeFinal()
    ->toBeReadonly();

/*
|--------------------------------------------------------------------------
| Module models
|--------------------------------------------------------------------------
*/

arch('module models should extend Eloquent Model')
    ->expect('Modules\*\Models')
    ->classes()
    ->toExtend(Model::class);

arch('models should not be final')
    ->expect(['App\Models', 'Modules\*\Models'])
    ->classes()
    ->not->toBeFinal();

arch('module models should not be used in form requests')
    ->expect('Modules\*\Models')
    ->not->toBeUsedIn('Modules\*\Requests');

arch('module models should not be used in payloads')
    ->expect('Modules\*\Models')
    ->not->toBeUsedIn('Modules\*\Payloads');

/*
|--------------------------------------------------------------------------
| Module controllers
|--------------------------------------------------------------------------
*/

arch('module controllers should be invokable')
    ->expect('Modules\*\Controllers')
    ->classes()
    ->toBeInvokable();

arch('module controllers should not use Model')
    ->expect('Modules\*\Controllers')
    ->classes()
    ->not->toUse(Model::class);

arch('module controllers should have Controller suffix')
    ->expect('Modules\*\Controllers')
    ->toHaveSuffix('Controller');

arch('module controllers should only have construct and invoke')
    ->expect('Modules\*\Controllers')
    ->classes()
    ->not->toHavePublicMethodsBesides(['__construct', '__invoke']);

/*
|--------------------------------------------------------------------------
| Module actions
|--------------------------------------------------------------------------
*/

arch('module actions should have handle method')
    ->expect('Modules\*\Actions')
    ->classes()
    ->toHaveMethod('handle');

arch('module actions should have Action suffix')
    ->expect('Modules\*\Actions')
    ->toHaveSuffix('Action');

arch('module actions should only have construct and handle')
    ->expect('Modules\*\Actions')
    ->classes()
    ->not->toHavePublicMethodsBesides(['__construct', 'handle']);

arch('module actions should not use HTTP Request')
    ->expect(Request::class)
    ->not->toBeUsedIn('Modules\*\Actions');

arch('module actions should not use abort helpers')
    ->expect(['abort', 'abort_if', 'abort_unless'])
    ->not->toBeUsedIn('Modules\*\Actions');

/*
|--------------------------------------------------------------------------
| Module requests
|--------------------------------------------------------------------------
*/

it('module list requests extend PaginationRequest', function (): void {
    $listRequests = glob(base_path('modules/*/Requests/V1/*ListRequest.php')) ?: [];

    expect($listRequests)->not->toBeEmpty();

    foreach ($listRequests as $file) {
        $module = basename(dirname($file, 3));
        $request = basename($file, '.php');
        $class = "Modules\\{$module}\\Requests\\V1\\{$request}";

        expect($class)->toExtend(PaginationRequest::class);
    }
});

/*
|--------------------------------------------------------------------------
| Module services
|--------------------------------------------------------------------------
*/

arch('module services should have Service suffix')
    ->expect('Modules\*\Services')
    ->toHaveSuffix('Service');

arch('module services should not use HTTP Request')
    ->expect(Request::class)
    ->not->toBeUsedIn('Modules\*\Services');

/*
|--------------------------------------------------------------------------
| Module payloads
|--------------------------------------------------------------------------
*/

arch('module payloads should have Payload suffix')
    ->expect('Modules\*\Payloads')
    ->toHaveSuffix('Payload');

arch('module payloads should have toArray method')
    ->expect('Modules\*\Payloads')
    ->classes()
    ->toHaveMethod('toArray');

/*
|--------------------------------------------------------------------------
| Module requests
|--------------------------------------------------------------------------
*/

arch('module requests should extend FormRequest and have rules')
    ->expect('Modules\*\Requests')
    ->classes()
    ->toExtend(FormRequest::class)
    ->toHaveMethod('rules');

arch('module requests should have Request suffix')
    ->expect('Modules\*\Requests')
    ->toHaveSuffix('Request');

/*
|--------------------------------------------------------------------------
| Module resources, filters, providers and seeders
|--------------------------------------------------------------------------
*/

arch('module resources should extend JsonResource')
    ->expect('Modules\*\Resources')
    ->classes()
    ->toExtend(JsonResource::class);

arch('module builders should extend BaseQueryBuilder')
    ->expect('Modules\*\Builders')
    ->classes()
    ->toExtend(BaseQueryBuilder::class);

arch('module providers should extend ServiceProvider')
    ->expect('Modules\*\Providers')
    ->classes()
    ->toExtend(ServiceProvider::class);

arch('module seeders should have run method')
    ->expect('Modules\*\Database\Seeders')
    ->classes()
    ->toHaveMethod('run');

/*
|--------------------------------------------------------------------------
| Module factories
|--------------------------------------------------------------------------
*/

arch('module factories should extend Factory')
    ->expect('Modules\*\Database\Factories')
    ->classes()
    ->toExtend(Factory::class);

arch('module factories should have definition method')
    ->expect('Modules\*\Database\Factories')
    ->classes()
    ->toHaveMethod('definition');

/*
|--------------------------------------------------------------------------
| Database
|--------------------------------------------------------------------------
*/

arch('seeders should have run method')
    ->expect('Database\Seeders')
    ->classes()
    ->toHaveMethod('run');

/*
|--------------------------------------------------------------------------
| Module isolation
|--------------------------------------------------------------------------
*/

arch('modules should be isolated')
    ->expect('Modules\*\*')
    ->toOnlyBeUsedIn([
        'Modules\*\*',
        'App\Providers',
        'App\Console',
        'App\Builders',
        'Tests',
        'Database\Seeders',
        'Database\Factories',
        'Modules\*\Database',
    ]);

/*
|--------------------------------------------------------------------------
| Module communication (public API seam)
|--------------------------------------------------------------------------
|
| The rules below are manual it() tests, not arch() assertions, because the
| arch() DSL cannot express them:
|
| - "Not used by OTHER modules" needs per-module self-exclusion. The arch()
|   toOnlyBeUsedIn()/toBeUsedIn() wildcards match every module, including
|   the owning one, so a cross-module internal import would pass the
|   "modules should be isolated" test above. This enforces the module
|   communication rule: models + contracts are the public API seam of a
|   module; Actions, Services, Payloads, Support, Builders, Enums are
|   internal layers that must not be imported by another module.
| - File-level checks (seeder cross-calls, ListRequest extension) iterate
|   directories because not every module has every folder, and the arch()
|   pattern "*ListRequest" crashes the Symfony Finder on missing
|   directories (e.g. Media and Organization have no Requests folder).
|
| Do not refactor these back into arch() assertions without proving the
| constraint is still enforced.
|
|--------------------------------------------------------------------------
*/

it('module internal layers are not referenced by other modules', function (): void {
    $internalLayers = ['Actions', 'Services', 'Payloads', 'Support', 'Builders', 'Enums'];

    $modules = array_map(basename(...), glob(base_path('modules/*'), GLOB_ONLYDIR) ?: []);

    $crossReferences = [];

    foreach ($modules as $source) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(base_path("modules/{$source}"), FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = (string) file_get_contents($file->getPathname());

            foreach ($modules as $target) {
                if ($source === $target) {
                    continue;
                }

                foreach ($internalLayers as $layer) {
                    if (str_contains($content, "Modules\\{$target}\\{$layer}\\")) {
                        $crossReferences[] = sprintf(
                            '%s references Modules\%s\%s',
                            str_replace(base_path('modules/'), '', $file->getPathname()),
                            $target,
                            $layer
                        );
                    }
                }
            }
        }
    }

    expect($crossReferences)->toBeEmpty();
});

it('module seeders do not call seeders from other modules', function (): void {
    $modules = array_map(basename(...), glob(base_path('modules/*'), GLOB_ONLYDIR) ?: []);

    $violations = [];

    foreach ($modules as $source) {
        $seederDirectory = base_path("modules/{$source}/Database/Seeders");

        if (! is_dir($seederDirectory)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($seederDirectory, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = (string) file_get_contents($file->getPathname());

            foreach ($modules as $target) {
                if ($source === $target) {
                    continue;
                }

                if (str_contains($content, "Modules\\{$target}\\Database\\Seeders\\")) {
                    $violations[] = sprintf(
                        '%s references %s seeder',
                        $file->getBasename(),
                        $target
                    );
                }
            }
        }
    }

    expect($violations)->toBeEmpty();
});
