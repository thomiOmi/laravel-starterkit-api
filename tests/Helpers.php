<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Request;
use Illuminate\Testing\PendingCommand;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Models\Permission;
use Modules\IAM\Models\Role;
use Modules\IAM\Models\User;
use Nwidart\Modules\Contracts\ActivatorInterface;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Authenticate the given user with Sanctum.
 * By default creates a verified user.
 *
 * @param  array<int, string>  $abilities
 */
function loginAsUser(?User $user = null, array $abilities = ['*']): User
{
    $authenticatedUser = $user ?? UserFactory::new()->createOne(['email_verified_at' => now()]);

    Sanctum::actingAs($authenticatedUser, $abilities);

    return $authenticatedUser;
}

/**
 * Authenticate an unverified user with Sanctum.
 *
 * @param  array<int, string>  $abilities
 */
function loginAsUnverifiedUser(?User $user = null, array $abilities = ['*']): User
{
    $authenticatedUser = $user ?? UserFactory::new()->createOne(['email_verified_at' => null]);

    Sanctum::actingAs($authenticatedUser, $abilities);

    return $authenticatedUser;
}

/**
 * Authenticate a verified user and assign the given role.
 *
 * The role must already exist in the database (seed `IAMSeeder` in the test).
 *
 * @param  array<int, string>  $abilities
 */
function loginAsRole(RoleEnum $role, ?User $user = null, array $abilities = ['*']): User
{
    $authenticatedUser = loginAsUser($user, $abilities);

    $authenticatedUser->assignRole($role->value);

    return $authenticatedUser;
}

/**
 * Authenticate a verified super-admin user.
 *
 * @param  array<int, string>  $abilities
 */
function loginAsSuperAdmin(?User $user = null, array $abilities = ['*']): User
{
    return loginAsRole(RoleEnum::SuperAdmin, $user, $abilities);
}

/**
 * Authenticate a verified admin user.
 *
 * @param  array<int, string>  $abilities
 */
function loginAsAdmin(?User $user = null, array $abilities = ['*']): User
{
    return loginAsRole(RoleEnum::Admin, $user, $abilities);
}

/**
 * Authenticate a verified user with the basic "user" role.
 *
 * @param  array<int, string>  $abilities
 */
function loginAsUserRole(?User $user = null, array $abilities = ['*']): User
{
    return loginAsRole(RoleEnum::User, $user, $abilities);
}

/**
 * Ensure a permission row exists, without Media tests importing IAM
 * classes themselves (see the module-requires rule in ArchitectureTest).
 */
function ensurePermissionExists(string $permission, string $guard = 'sanctum'): void
{
    Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => $guard]);
}

/**
 * Ensure a role row exists, without Media tests importing IAM classes
 * themselves (see the module-requires rule in ArchitectureTest).
 */
function ensureRoleExists(string $role, string $guard = 'sanctum'): void
{
    Role::query()->firstOrCreate(['name' => $role, 'guard_name' => $guard]);
}

/**
 * Decode a JSON response body into an associative array.
 *
 * @return array<mixed>
 */
function responseData(Response $response): array
{
    $decoded = json_decode($response->getContent() ?: '{}', true);

    return is_array($decoded) ? $decoded : [];
}

/**
 * Assert the response matches the RFC 9457 problem details shape.
 *
 * @param  TestResponse<Response>  $response
 * @param  string|null  $type  Expected "type" value (contains match).
 * @return TestResponse<Response>
 */
function assertProblemResponse(TestResponse $response, int $status = 422, ?string $type = null): TestResponse
{
    $response->assertHeader('Content-Type', 'application/problem+json')
        ->assertStatus($status)
        ->assertJsonStructure([
            'type',
            'title',
            'status',
            'detail',
            'timestamp',
        ]);

    if ($type !== null) {
        $typeValue = $response->json('type');
        expect(is_string($typeValue) ? $typeValue : '')->toContain($type);
    }

    return $response;
}

/**
 * Assert the response matches the API success envelope.
 *
 * @param  TestResponse<Response>  $response
 * @param  string|null  $title  Expected "title" value (exact match).
 * @return TestResponse<Response>
 */
function assertSuccessResponse(TestResponse $response, int $status = 200, ?string $title = null): TestResponse
{
    $response->assertStatus($status);

    if ($status >= 200 && $status < 300 && $status !== 204 && $status !== 205) {
        $response->assertJsonStructure([
            'status',
            'title',
            'detail',
            'data',
        ]);

        if ($title !== null) {
            expect($response->json('title'))->toBe($title);
        }
    }

    return $response;
}

/**
 * Assert the response is a paginated success envelope.
 *
 * @param  TestResponse<Response>  $response
 * @return TestResponse<Response>
 */
function assertPaginatedResponse(TestResponse $response): TestResponse
{
    $response->assertJsonStructure(['status', 'data', 'meta']);

    $meta = $response->json('meta');
    throw_unless(is_array($meta), RuntimeException::class, 'Paginated response meta is not an array.');

    expect($meta)
        ->toHaveKeys(['per_page', 'has_more'])
        ->and($meta['per_page'])->toBeInt()->toBeGreaterThan(0)
        ->and($meta['has_more'])->toBeBool();

    return $response;
}

/**
 * Assert the response carries the X-Trace-ID header.
 *
 * @param  TestResponse<Response>  $response
 * @return TestResponse<Response>
 */
function assertHasTraceId(TestResponse $response): TestResponse
{
    $response->assertHeader('X-Trace-ID');
    expect($response->headers->get('X-Trace-ID'))->not->toBeEmpty();

    return $response;
}

/**
 * Assert the response carries the Sunset header in RFC 7231 format.
 *
 * @param  TestResponse<Response>  $response
 * @return TestResponse<Response>
 */
function assertSunsetHeader(TestResponse $response, string $date): TestResponse
{
    $response->assertHeader('Sunset');
    expect($response->headers->get('Sunset'))->toBe(new DateTimeImmutable($date)->format(DateTimeInterface::RFC7231));

    return $response;
}

/**
 * Run an artisan command and return its pending command for assertions.
 *
 * @param  array<string, mixed>  $parameters
 */
function artisanCommand(TestCase $test, string $command, array $parameters = []): PendingCommand
{
    $pending = $test->artisan($command, $parameters);

    throw_unless($pending instanceof PendingCommand, LogicException::class, 'Mocked console output must be enabled to assert on the pending command.');

    return $pending;
}

/**
 * Decode a JSON file into an array.
 *
 * @return array<mixed, mixed>
 */
function decodeModuleJson(string $path): array
{
    $json = json_decode(file_get_contents($path) ?: '', true);

    throw_unless(is_array($json), RuntimeException::class, "Invalid JSON in {$path}");

    return $json;
}

/**
 * Directory holding this worker's dependency-check fixtures.
 *
 * Under `pest --parallel` every worker process is spawned with unique
 * TEST_TOKEN and UNIQUE_TEST_TOKEN env vars (paratest's WrapperWorker calls
 * fillEnvWithTokens), so one root per worker keeps concurrently running test
 * files from deleting each other's fixtures. Serial runs have no tokens and
 * fall back to the process id. Roots live in the system temp dir because
 * nwidart's repository and activator consume the configured absolute paths
 * and do not require the fixtures to be below base_path.
 */
function dependencyCheckFixtureRoot(): string
{
    $prefix = sys_get_temp_dir().DIRECTORY_SEPARATOR.'module-dependency-check-';

    $raw = Request::server('UNIQUE_TEST_TOKEN') ?? Env::get('UNIQUE_TEST_TOKEN', Request::server('TEST_TOKEN') ?? Env::get('TEST_TOKEN'));

    $token = preg_replace('/[^A-Za-z0-9_-]/', '', is_scalar($raw) ? (string) $raw : (string) getmypid());

    if ($token === null || $token === '') {
        return $prefix.getmypid();
    }

    return $prefix.$token;
}

/**
 * Scaffold a fixture module under the per-worker dependency-check fixture
 * root for module tooling tests. Never touches the real modules/ directory
 * or the real modules_statuses.json.
 *
 * @param  list<string>  $requires
 */
function writeFixtureModule(string $name, array $requires = [], bool $enabled = true): string
{
    $root = dependencyCheckFixtureRoot();
    $files = resolve(Filesystem::class);
    $modulePath = "{$root}/modules/{$name}";

    $files->makeDirectory($modulePath, 0755, true);

    $manifest = [
        'name' => $name,
        'alias' => strtolower($name),
        'priority' => 0,
        'providers' => [],
    ];

    if ($requires !== []) {
        $manifest['requires'] = $requires;
    }

    $manifestJson = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    throw_if($manifestJson === false, RuntimeException::class, "Failed to encode module.json for {$name}.");

    $files->put($modulePath.'/module.json', $manifestJson);
    $files->put($modulePath.'/composer.json', '{}');

    $statusesPath = "{$root}/statuses.json";
    $statuses = is_file($statusesPath) ? decodeModuleJson($statusesPath) : [];

    $statuses[$name] = $enabled;

    $statusesJson = json_encode($statuses, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    throw_if($statusesJson === false, RuntimeException::class, 'Failed to encode the fixture statuses file.');

    $files->put($statusesPath, $statusesJson);

    return $modulePath;
}

/**
 * Restore default module paths and drop cached singletons.
 */
function restoreDefaultModulePaths(): void
{
    config()->set('modules.paths.modules', base_path('modules'));
    config()->set('modules.activators.file.statuses-file', base_path('modules_statuses.json'));
    forgetModuleSingletons();
}

/**
 * Remove the fixture module root used by module tooling tests. Omit $root
 * for the per-worker dependency-check fixture root; pass a base_path
 * relative path to target another fixture root explicitly.
 */
function clearFixtureModules(?string $root = null): void
{
    resolve(Filesystem::class)->deleteDirectory($root === null
        ? dependencyCheckFixtureRoot()
        : base_path($root));
    restoreDefaultModulePaths();
}

/**
 * Drop resolved nwidart singletons so subsequent config overrides take
 * effect for module tooling tests.
 */
function forgetModuleSingletons(): void
{
    if (app()->bound(RepositoryInterface::class)) {
        resolve(RepositoryInterface::class)->resetModules();
    }

    if (app()->bound('modules')) {
        resolve('modules')->resetModules();
    }

    app()->forgetInstance('modules');
    app()->forgetInstance(RepositoryInterface::class);
    app()->forgetInstance(ActivatorInterface::class);
}

/**
 * Point the nwidart repository and activator at the dependency-check fixture
 * root and drop any already-resolved singletons, so module tooling tests run
 * against fixture state only. Omit $root for the per-worker dependency-check
 * fixture root; pass a base_path relative path to target another fixture
 * root explicitly.
 */
function bindFixtureModulePaths(?string $root = null): void
{
    $target = $root === null ? dependencyCheckFixtureRoot() : base_path($root);

    config()->set('modules.paths.modules', "{$target}/modules");
    config()->set('modules.activators.file.statuses-file', "{$target}/statuses.json");
    forgetModuleSingletons();
}
