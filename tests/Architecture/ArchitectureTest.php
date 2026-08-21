<?php

declare(strict_types=1);

use App\Builders\BaseQueryBuilder;
use App\Http\Requests\PaginationRequest;
use Illuminate\Console\Command;
use Illuminate\Contracts\Container\ContextualAttribute;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\ServiceProvider;
use PHPUnit\Framework\Assert;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

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

arch('avoid dump and exit helpers')
    ->expect(['dd', 'ddd', 'dump', 'ray', 'exit'])
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

arch('app concerns should be traits')
    ->expect('App\Concerns')
    ->toBeTraits();

arch('app enums should be enums')
    ->expect('App\Enums')
    ->toBeEnums();

/*
|--------------------------------------------------------------------------
| App future components (Pest Laravel preset compatibility)
|--------------------------------------------------------------------------
|
| These rules mirror the Pest "Laravel" preset (pestphp/pest
| ArchPresets\Laravel). Every preset rule is included, even for folders
| that do not exist yet: arch() treats an empty namespace as a no-op, so
| the rules pass trivially until a folder is introduced, then enforce the
| convention from day one.
|
| Deviations from the preset are limited to:
| - App\Http toOnlyBeUsedIn is widened to include Modules (the public
|   seam), App\Providers, and Tests.
| - App\Providers not->toBeUsed ignores Modules (providers bootstrap
|   module features through service providers).
|
*/

arch('app policies should have Policy suffix')
    ->expect('App\Policies')
    ->classes()
    ->toHaveSuffix('Policy');

arch('app traits should be traits')
    ->expect('App\Traits')
    ->toBeTraits();

arch('app mail should be queued mailables')
    ->expect('App\Mail')
    ->classes()
    ->toExtend(Mailable::class)
    ->toImplement(ShouldQueue::class);

arch('app jobs should be queued with handle')
    ->expect('App\Jobs')
    ->classes()
    ->toImplement(ShouldQueue::class)
    ->toHaveMethod('handle');

arch('app listeners should have handle method')
    ->expect('App\Listeners')
    ->toHaveMethod('handle');

arch('app attributes should be contextual attributes')
    ->expect('App\Attributes')
    ->classes()
    ->toImplement(ContextualAttribute::class)
    ->toHaveAttribute(Attribute::class)
    ->toHaveMethod('resolve');

/*
|--------------------------------------------------------------------------
| App http layer
|--------------------------------------------------------------------------
*/

arch('app http should only be used in allowed layers')
    ->expect('App\Http')
    ->toOnlyBeUsedIn(['App\Http', 'App\Providers', 'Modules', 'Tests']);

arch('app http controllers should have Controller suffix')
    ->expect('App\Http\Controllers')
    ->classes()
    ->toHaveSuffix('Controller');

arch('app http controllers should only have standard methods')
    ->expect('App\Http\Controllers')
    ->not->toHavePublicMethodsBesides(['__construct', '__invoke', 'index', 'show', 'create', 'store', 'edit', 'update', 'destroy', 'middleware']);

arch('app http middleware should have handle method')
    ->expect('App\Http\Middleware')
    ->classes()
    ->toHaveMethod('handle');

arch('app http middleware should not have Middleware suffix')
    ->expect('App\Http\Middleware')
    ->classes()
    ->not->toHaveSuffix('Middleware');

arch('app http requests should extend FormRequest and have rules')
    ->expect('App\Http\Requests')
    ->classes()
    ->toExtend(FormRequest::class)
    ->toHaveMethod('rules');

arch('app http requests should have Request suffix')
    ->expect('App\Http\Requests')
    ->toHaveSuffix('Request');

arch('app http responses should implement Responsable')
    ->expect('App\Http\Responses')
    ->classes()
    ->toImplement(Responsable::class);

/*
|--------------------------------------------------------------------------
| App models, notifications, commands and providers
|--------------------------------------------------------------------------
*/

arch('app models should extend Eloquent Model')
    ->expect('App\Models')
    ->classes()
    ->toExtend(Model::class);

arch('app models should not have Model suffix')
    ->expect('App\Models')
    ->classes()
    ->not->toHaveSuffix('Model');

arch('app notifications should extend Notification')
    ->expect('App\Notifications')
    ->classes()
    ->toExtend(Notification::class);

arch('app commands should extend Command and have handle')
    ->expect('App\Console\Commands')
    ->classes()
    ->toExtend(Command::class)
    ->toHaveMethod('handle');

arch('app commands should have Command suffix')
    ->expect('App\Console\Commands')
    ->classes()
    ->toHaveSuffix('Command');

arch('app providers should extend ServiceProvider')
    ->expect('App\Providers')
    ->classes()
    ->toExtend(ServiceProvider::class);

arch('app providers should have ServiceProvider suffix')
    ->expect('App\Providers')
    ->toHaveSuffix('ServiceProvider');

arch('app providers should not be used')
    ->expect('App\Providers')
    ->classes()
    ->not->toBeUsed()
    ->ignoring('Modules');

/*
|--------------------------------------------------------------------------
| App namespace boundaries
|--------------------------------------------------------------------------
*/

arch('app classes should not be enums outside App\Enums')
    ->expect('App')
    ->not->toBeEnums()
    ->ignoring('App\Enums');

arch('app classes should not implement Throwable outside App\Exceptions')
    ->expect('App')
    ->not->toImplement(Throwable::class)
    ->ignoring('App\Exceptions');

arch('app exceptions should implement Throwable')
    ->expect('App\Exceptions')
    ->classes()
    ->toImplement(Throwable::class)
    ->ignoring('App\Exceptions\Handler');

arch('app classes should not extend Model outside App\Models')
    ->expect('App')
    ->not->toExtend(Model::class)
    ->ignoring('App\Models');

arch('app classes should not extend FormRequest outside App\Http\Requests')
    ->expect('App')
    ->not->toExtend(FormRequest::class)
    ->ignoring('App\Http\Requests');

arch('app classes should not extend Command outside App\Console\Commands')
    ->expect('App')
    ->not->toExtend(Command::class)
    ->ignoring('App\Console\Commands');

arch('app classes should not extend Notification outside App\Notifications')
    ->expect('App')
    ->not->toExtend(Notification::class)
    ->ignoring('App\Notifications');

arch('app classes should not extend ServiceProvider outside App\Providers')
    ->expect('App')
    ->not->toExtend(ServiceProvider::class)
    ->ignoring('App\Providers');

arch('app Controller suffix should only be used in App\Http\Controllers')
    ->expect('App')
    ->not->toHaveSuffix('Controller')
    ->ignoring('App\Http\Controllers');

arch('app ServiceProvider suffix should only be used in App\Providers')
    ->expect('App')
    ->not->toHaveSuffix('ServiceProvider')
    ->ignoring('App\Providers');

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
    ->expect('Modules\*\Http\Controllers')
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

arch('module policies should have Policy suffix')
    ->expect('Modules\*\Policies')
    ->toHaveSuffix('Policy');

arch('module policies should be final')
    ->expect('Modules\*\Policies')
    ->classes()
    ->toBeFinal();

it('module scaffolds follow the kit folder structure', function (): void {
    $requiredFolders = [
        'app/Providers',
        'config',
        'database/factories',
        'database/migrations',
        'database/seeders',
        'routes',
        'tests/Feature',
        'tests/Unit',
    ];

    $modules = array_map(basename(...), glob(base_path('modules/*'), GLOB_ONLYDIR) ?: []);

    $missing = [];

    foreach ($modules as $module) {
        if (! is_file(base_path("modules/{$module}/module.json"))) {
            continue;
        }

        foreach ($requiredFolders as $folder) {
            if (! is_dir(base_path("modules/{$module}/{$folder}"))) {
                $missing[] = "{$module}/{$folder}";
            }
        }
    }

    expect($missing)->toBeEmpty();
});

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
    ->not->toBeUsedIn('Modules\*\Http\Requests');

arch('module models should not be used in payloads')
    ->expect('Modules\*\Models')
    ->not->toBeUsedIn('Modules\*\Payloads');

/*
|--------------------------------------------------------------------------
| Module controllers
|--------------------------------------------------------------------------
|
| Controllers may be invokable (IAM style) or resource controllers
| (generated by the module:make scaffold apiResource routes). Both are
| valid, so only the shared constraints are enforced below.
|
*/

arch('module controllers should not use Model')
    ->expect('Modules\*\Http\Controllers')
    ->classes()
    ->not->toUse(Model::class);

arch('module controllers should have Controller suffix')
    ->expect('Modules\*\Http\Controllers')
    ->toHaveSuffix('Controller');

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
    $listRequests = glob(base_path('modules/*/app/Http/Requests/V1/*ListRequest.php')) ?: [];

    expect($listRequests)->not->toBeEmpty();

    foreach ($listRequests as $file) {
        $module = basename(dirname($file, 5));
        $request = basename($file, '.php');
        $class = "Modules\\{$module}\\Http\\Requests\\V1\\{$request}";

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
    ->expect('Modules\*\Http\Requests')
    ->classes()
    ->toExtend(FormRequest::class)
    ->toHaveMethod('rules');

arch('module requests should have Request suffix')
    ->expect('Modules\*\Http\Requests')
    ->toHaveSuffix('Request');

/*
|--------------------------------------------------------------------------
| Module resources, filters, providers and seeders
|--------------------------------------------------------------------------
*/

arch('module resources should extend JsonResource')
    ->expect('Modules\*\Http\Resources')
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
| The rules below are manual it() tests, not arch() assertions, because a
| single arch() assertion cannot express them:
|
| - "Not used by OTHER modules" needs per-module self-exclusion. The arch()
|   toOnlyBeUsedIn()/toBeUsedIn() wildcards match every module, including
|   the owning one (the pattern regex is unanchored, `*` = one segment),
|   and ignoring() filters both sides of the check, so a cross-module
|   internal import would pass the "modules should be isolated" test
|   above. This enforces the module communication rule: models + contracts
|   are the public API seam of a module; Actions, Services, Payloads,
|   Support, Builders, Enums are internal layers that must not be imported
|   by another module. A per-module arch() loop could express this, but
|   the file-level scan below is kept because it also catches references
|   in non-class files (Routes, config-style arrays) and is easier to
|   audit.
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
        $seederDirectory = base_path("modules/{$source}/database/seeders");

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

/*
|--------------------------------------------------------------------------
| Module dependencies
|--------------------------------------------------------------------------
|
| Modules declare their cross-module dependencies in the "requires" field
| of module.json. The rules below are manual it() tests, not arch()
| assertions, because they read manifest files and scan directories: the
| dependency graph lives in JSON, not in class references the arch()
| runner can see.
|
*/

/**
 * Read the "requires" field of a module.json manifest.
 *
 * @return list<string>
 */
function moduleRequires(string $path): array
{
    $json = is_file($path) ? file_get_contents($path) : false;

    if ($json === false) {
        return [];
    }

    $manifest = json_decode($json, true);

    if (! is_array($manifest) || ! isset($manifest['requires']) || ! is_array($manifest['requires'])) {
        return [];
    }

    return array_values(array_filter($manifest['requires'], is_string(...)));
}

/**
 * Detect cycles in the module requires graph.
 *
 * @param  array<string, list<string>>  $graph
 * @return list<string>
 */
function moduleGraphCycles(array $graph): array
{
    $cycles = [];

    foreach (array_keys($graph) as $module) {
        $cycle = moduleGraphFindCycle($graph, $module, []);

        if ($cycle !== null) {
            $cycles[] = implode(' -> ', $cycle);
        }
    }

    return $cycles;
}

/**
 * @param  array<string, list<string>>  $graph
 * @param  list<string>  $path
 * @return list<string>|null
 */
function moduleGraphFindCycle(array $graph, string $node, array $path): ?array
{
    if (in_array($node, $path, true)) {
        return [...$path, $node];
    }

    foreach ($graph[$node] ?? [] as $dependency) {
        $cycle = moduleGraphFindCycle($graph, $dependency, [...$path, $node]);

        if ($cycle !== null) {
            return $cycle;
        }
    }

    return null;
}

it('module dependencies are declared in module.json', function (): void {
    $modules = array_map(basename(...), glob(base_path('modules/*'), GLOB_ONLYDIR) ?: []);
    $violations = [];

    foreach ($modules as $source) {
        $manifestPath = base_path("modules/{$source}/module.json");
        $declared = moduleRequires($manifestPath);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(base_path("modules/{$source}"), FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = (string) file_get_contents($file->getPathname());

            foreach ($modules as $target) {
                if ($source === $target || in_array($target, $declared, true)) {
                    continue;
                }

                if (str_contains($content, "Modules\\{$target}\\")) {
                    $violations[] = sprintf(
                        '%s uses Modules\%s but %s/module.json does not declare it in "requires"',
                        str_replace(base_path('modules/'), '', $file->getPathname()),
                        $target,
                        $source
                    );
                }
            }
        }
    }

    expect($violations)->toBeEmpty();
});

it('module dependency graph has no cycles', function (): void {
    $modules = array_map(basename(...), glob(base_path('modules/*'), GLOB_ONLYDIR) ?: []);
    $graph = [];

    foreach ($modules as $module) {
        $graph[$module] = moduleRequires(base_path("modules/{$module}/module.json"));
    }

    expect(moduleGraphCycles($graph))->toBeEmpty();
});

it('core modules do not depend on business modules', function (): void {
    $coreModules = array_values(array_filter(
        config()->array('modules.core', ['IAM', 'Media', 'Organization']),
        is_string(...)
    ));
    $violations = [];

    foreach ($coreModules as $core) {
        $manifestPath = base_path("modules/{$core}/module.json");
        $declared = moduleRequires($manifestPath);

        $nonCoreDeps = array_diff($declared, $coreModules);

        if ($nonCoreDeps !== []) {
            $violations[] = sprintf('%s depends on business module(s): %s', $core, implode(', ', $nonCoreDeps));
        }
    }

    expect($violations)->toBeEmpty();
});

/*
|--------------------------------------------------------------------------
| Module tenancy
|--------------------------------------------------------------------------
*/

/**
 * Modules whose models must be tenant-scoped.
 *
 * Empty until business modules with tenant data exist; every future
 * business module must be added here unless all its models are genuinely
 * global.
 *
 * @return list<string>
 */
function tenantScopedModules(): array
{
    return [];
}

it('tenant-scoped models use BelongsToTenant trait', function (): void {
    $tenantScopedModules = tenantScopedModules();
    $violations = [];

    foreach ($tenantScopedModules as $module) {
        $modelPath = base_path("modules/{$module}/app/Models");

        if (! is_dir($modelPath)) {
            continue;
        }

        foreach (glob("{$modelPath}/*.php") ?: [] as $file) {
            $class = "Modules\\{$module}\\Models\\".basename($file, '.php');

            if (! class_exists($class)) {
                continue;
            }

            $traits = class_uses_recursive($class);

            if (! in_array(BelongsToTenant::class, $traits, true)) {
                $violations[] = "{$class} does not use BelongsToTenant trait";
            }
        }
    }

    expect($violations)->toBeEmpty();
});

/**
 * Best-effort guard: regex cannot cover every way tenant_id might be queried
 * (e.g. DB::raw(), query builder assigned to a variable then chained across
 * lines, dynamic column names). This test catches the common direct
 * where/orWhere patterns; it is not an absolute guarantee.
 */
it('tenant_id is not manually queried outside BelongsToTenant scope', function (): void {
    $modules = array_map(basename(...), glob(base_path('modules/*'), GLOB_ONLYDIR) ?: []);
    $violations = [];

    // Matches: where('tenant_id', ...), orWhere('tenant_id', ...),
    // where(['tenant_id' => ...]), orWhere(['tenant_id' => ...])
    // Case-insensitive on the where/orWhere token; allows an optional leading '[' for
    // array-syntax where().
    $pattern = "/(?:or)?[Ww]here\\(\\s*\\[?\\s*['\"]tenant_id['\"]/";

    foreach ($modules as $module) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(base_path("modules/{$module}"), FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            // Model classes exempt — trait/scope internals may legitimately reference the column.
            if (str_contains($file->getPathname(), DIRECTORY_SEPARATOR.'Models'.DIRECTORY_SEPARATOR)) {
                continue;
            }

            $content = (string) file_get_contents($file->getPathname());

            if (preg_match($pattern, $content)) {
                $violations[] = str_replace(base_path('modules/'), '', $file->getPathname());
            }
        }
    }

    expect($violations)->toBeEmpty();
});
