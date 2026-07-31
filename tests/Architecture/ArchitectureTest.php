<?php

declare(strict_types=1);

use App\Support\Filters\BaseFilter;
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

describe('coding standards', function () {
    arch('app uses strict types')
        ->expect(['App', 'Modules', 'Tests'])
        ->toUseStrictTypes();

    arch('app uses strict equality')
        ->expect(['App', 'Modules', 'Tests'])
        ->toUseStrictEquality();

    arch('avoid debugging functions')
        ->expect(['dd', 'ddd', 'dump', 'ray', 'var_dump', 'print_r', 'exit'])
        ->not->toBeUsed();

    arch('avoid insecure functions')
        ->expect(['md5', 'sha1', 'uniqid', 'tempnam', 'str_shuffle', 'shuffle', 'array_rand'])
        ->not->toBeUsed()
        ->ignoring('App\Providers');

    arch('avoid insecure random functions')
        ->expect(['rand', 'mt_rand'])
        ->not->toBeUsed()
        ->ignoring(['Database\Factories', 'Tests', 'Modules\*\Database\Factories']);

    arch('avoid code execution functions')
        ->expect(['eval', 'exec', 'shell_exec', 'system', 'passthru', 'create_function', 'unserialize', 'dl', 'assert'])
        ->not->toBeUsed();

    arch('avoid variable injection functions')
        ->expect(['extract', 'parse_str', 'mb_parse_str'])
        ->not->toBeUsed()
        ->ignoring('App\Providers');

    arch('avoid deprecated PHP functions')
        ->expect(['ereg', 'eregi', 'mysql_connect', 'mysql_pconnect', 'mysql_query', 'mysql_select_db', 'mysql_fetch_array', 'mysql_fetch_assoc', 'mysql_fetch_object', 'mysql_fetch_row', 'mysql_num_rows', 'mysql_affected_rows', 'mysql_free_result', 'mysql_insert_id', 'mysql_error', 'mysql_real_escape_string'])
        ->not->toBeUsed();

    arch('avoid debug tracing in production')
        ->expect(['debug_backtrace', 'debug_print_backtrace', 'debug_zval_dump', 'phpinfo'])
        ->not->toBeUsed();

    arch('avoid sleep in application code')
        ->expect(['sleep', 'usleep'])
        ->not->toBeUsed();

    arch('avoid env() outside of config')
        ->expect('env')
        ->not->toBeUsed()
        ->ignoring('config');

    arch('tests should not use PHPUnit assertions')
        ->expect('Tests')
        ->not->toUse(Assert::class);
});

describe('app namespace boundaries', function () {
    arch('app classes should not be enums outside App\Enums')
        ->expect('App')
        ->not->toBeEnums()
        ->ignoring('App\Enums');

    arch('app classes should not implement Throwable outside App\Exceptions')
        ->expect('App')
        ->not->toImplement(Throwable::class)
        ->ignoring('App\Exceptions');

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

    arch('app classes should not extend Mailable outside App\Mail')
        ->expect('App')
        ->not->toExtend(Mailable::class)
        ->ignoring('App\Mail');

    arch('app ServiceProvider suffix should only be used in App\Providers')
        ->expect('App')
        ->not->toHaveSuffix('ServiceProvider')
        ->ignoring('App\Providers');

    arch('app Controller suffix should only be used in App\Http\Controllers')
        ->expect('App')
        ->not->toHaveSuffix('Controller')
        ->ignoring('App\Http\Controllers');
});

describe('app shared layer', function () {
    arch('app traits should be traits')
        ->expect('App\Traits')
        ->toBeTraits();

    arch('app concerns should be traits')
        ->expect('App\Concerns')
        ->toBeTraits();

    arch('app enums should be enums')
        ->expect('App\Enums')
        ->toBeEnums()
        ->ignoring('App\Enums\Concerns');

    arch('app contracts should be interfaces')
        ->expect('App\Contracts')
        ->toBeInterfaces();
});

describe('app features', function () {
    arch('app features should be classes')
        ->expect('App\Features')
        ->toBeClasses()
        ->ignoring('App\Features\Concerns');

    arch('app features should have resolve method')
        ->expect('App\Features')
        ->toHaveMethod('resolve')
        ->ignoring('App\Features\Concerns');
});

describe('app exceptions', function () {
    arch('app exceptions should implement Throwable')
        ->expect('App\Exceptions')
        ->classes()
        ->toImplement(Throwable::class)
        ->ignoring('App\Exceptions\Handler');
});

describe('app http layer', function () {
    arch('app http should only be used in allowed layers')
        ->expect('App\Http')
        ->toOnlyBeUsedIn(['App\Http', 'App\Providers', 'Modules', 'Tests'])
        ->ignoring('bootstrap/app.php');

    arch('app http controllers should have Controller suffix')
        ->expect('App\Http\Controllers')
        ->classes()
        ->toHaveSuffix('Controller');

    arch('app http controllers should only have conventional methods')
        ->expect('App\Http\Controllers')
        ->not->toHavePublicMethodsBesides([
            '__construct', '__invoke', 'index', 'show', 'create',
            'store', 'edit', 'update', 'destroy', 'middleware',
        ]);

    arch('app http middleware should have handle method')
        ->expect('App\Http\Middleware')
        ->classes()
        ->toHaveMethod('handle');

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
});

describe('app console', function () {
    arch('commands should have Command suffix')
        ->expect('App\Console\Commands')
        ->classes()
        ->toHaveSuffix('Command');

    arch('commands should extend Command and have handle')
        ->expect('App\Console\Commands')
        ->classes()
        ->toExtend(Command::class)
        ->toHaveMethod('handle');
});

describe('app notifications', function () {
    arch('notifications should extend Notification')
        ->expect('App\Notifications')
        ->classes()
        ->toExtend(Notification::class);
});

describe('app providers', function () {
    arch('providers should extend ServiceProvider')
        ->expect('App\Providers')
        ->classes()
        ->toExtend(ServiceProvider::class);

    arch('providers should not be used')
        ->expect('App\Providers')
        ->classes()
        ->not->toBeUsed();

    arch('providers should have ServiceProvider suffix')
        ->expect('App\Providers')
        ->toHaveSuffix('ServiceProvider');
});

describe('app models & mail & jobs & policies & attributes', function () {
    arch('app models should extend Eloquent Model')
        ->expect('App\Models')
        ->classes()
        ->toExtend(Model::class);

    arch('app models should not have Model suffix')
        ->expect('App\Models')
        ->classes()
        ->not->toHaveSuffix('Model');

    arch('app mail should extend Mailable')
        ->expect('App\Mail')
        ->classes()
        ->toExtend(Mailable::class);

    arch('app mail should implement ShouldQueue')
        ->expect('App\Mail')
        ->classes()
        ->toImplement(ShouldQueue::class);

    arch('app jobs should implement ShouldQueue')
        ->expect('App\Jobs')
        ->classes()
        ->toImplement(ShouldQueue::class);

    arch('app jobs should have handle method')
        ->expect('App\Jobs')
        ->classes()
        ->toHaveMethod('handle');

    arch('app listeners should have handle method')
        ->expect('App\Listeners')
        ->toHaveMethod('handle');

    arch('app policies should have Policy suffix')
        ->expect('App\Policies')
        ->classes()
        ->toHaveSuffix('Policy');

    arch('app attributes should be contextual attributes')
        ->expect('App\Attributes')
        ->classes()
        ->toImplement(ContextualAttribute::class)
        ->toHaveAttribute(Attribute::class)
        ->toHaveMethod('resolve');
});

describe('module core structure', function () {
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
});

describe('module models', function () {
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
});

describe('module controllers', function () {
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
});

describe('module actions', function () {
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
});

describe('module services', function () {
    arch('module services should have Service suffix')
        ->expect('Modules\*\Services')
        ->toHaveSuffix('Service');

    arch('module services should not use HTTP Request')
        ->expect(Request::class)
        ->not->toBeUsedIn('Modules\*\Services');
});

describe('module payloads', function () {
    arch('module payloads should have Payload suffix')
        ->expect('Modules\*\Payloads')
        ->toHaveSuffix('Payload');

    arch('module payloads should have toArray method')
        ->expect('Modules\*\Payloads')
        ->classes()
        ->toHaveMethod('toArray');
});

describe('module requests', function () {
    arch('module requests should extend FormRequest and have rules')
        ->expect('Modules\*\Requests')
        ->classes()
        ->toExtend(FormRequest::class)
        ->toHaveMethod('rules');

    arch('module requests should have Request suffix')
        ->expect('Modules\*\Requests')
        ->toHaveSuffix('Request');
});

describe('module resources, filters, providers, seeders', function () {
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
});

describe('module factories', function () {
    arch('module factories should extend Factory')
        ->expect('Modules\*\Database\Factories')
        ->classes()
        ->toExtend(Factory::class);

    arch('module factories should have definition method')
        ->expect('Modules\*\Database\Factories')
        ->classes()
        ->toHaveMethod('definition');
});

describe('module environment isolation', function () {
    arch('modules should not use env() directly')
        ->expect('env')
        ->not->toBeUsedIn('Modules\*\*');
});

describe('database', function () {
    arch('seeders should have run method')
        ->expect('Database\Seeders')
        ->classes()
        ->toHaveMethod('run');
});

describe('module isolation', function () {
    arch('modules should be isolated')
        ->expect('Modules\*\*')
        ->toOnlyBeUsedIn('Modules\*\*')
        ->ignoring([
            'App\Providers',
            'App\Console',
            'App\Filters',
            'Tests',
            'Database\Seeders',
            'Database\Factories',
            'Modules\*\Database',
        ]);
});
