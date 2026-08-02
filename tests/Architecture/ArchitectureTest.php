<?php

declare(strict_types=1);

use App\Support\Filters\BaseFilter;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;
use PHPUnit\Framework\Assert;

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

arch('module filters should extend BaseFilter')
    ->expect('Modules\*\Filters')
    ->classes()
    ->toExtend(BaseFilter::class);

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
        'App\Filters',
        'Tests',
        'Database\Seeders',
        'Database\Factories',
        'Modules\*\Database',
    ]);
