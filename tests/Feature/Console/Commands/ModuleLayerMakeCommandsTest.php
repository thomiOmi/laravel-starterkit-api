<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Process;

beforeEach(function (): void {
    Process::fake();

    $files = app('files');
    $modulePath = base_path('tests/Fixtures/modules/Widget');

    config()->set('modules.paths.modules', base_path('tests/Fixtures/modules'));
    forgetModuleSingletons();

    $files->makeDirectory($modulePath, 0755, true);

    $moduleJson = json_encode([
        'name' => 'Widget',
        'alias' => 'widget',
        'priority' => 0,
        'providers' => [],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    if ($moduleJson === false) {
        throw new RuntimeException('Failed to encode the module.json fixture.');
    }

    $files->put($modulePath.'/module.json', $moduleJson);

    $files->put($modulePath.'/composer.json', '{}');
});

afterEach(function (): void {
    app('files')->deleteDirectory(base_path('tests/Fixtures/modules'));
});

describe('module layer commands generate convention-compliant files', function () {
    it('generates a strict-typed, attribute-based model in app/Models', function () {
        artisanCommand($this, 'module:make-model', ['model' => 'Product', 'module' => 'Widget'])
            ->assertSuccessful();

        $model = file_get_contents(base_path('tests/Fixtures/modules/Widget/app/Models/Product.php'));

        expect($model)->toContain('declare(strict_types=1)')
            ->toContain('namespace Modules\Widget\Models;')
            ->toContain('class Product extends Model')
            ->toContain('#[Fillable]')
            ->toContain('#[Hidden]')
            ->not->toContain('protected $fillable')
            ->not->toContain('use HasFactory')
            ->not->toContain('final class');
    });

    it('generates scopes into app/Models/Scopes', function () {
        artisanCommand($this, 'module:make-scope', ['name' => 'ActiveProduct', 'module' => 'Widget'])
            ->assertSuccessful();

        $scope = file_get_contents(base_path('tests/Fixtures/modules/Widget/app/Models/Scopes/ActiveProduct.php'));

        expect($scope)->toContain('declare(strict_types=1)')
            ->toContain('namespace Modules\Widget\Models\Scopes;')
            ->toContain('class ActiveProduct implements Scope');
    });

    it('generates final readonly actions with handle(): void', function () {
        artisanCommand($this, 'module:make-action', ['name' => 'RegisterProduct', 'module' => 'Widget'])
            ->assertSuccessful();

        $action = file_get_contents(base_path('tests/Fixtures/modules/Widget/app/Actions/RegisterProduct.php'));

        expect($action)->toContain('declare(strict_types=1)')
            ->toContain('final readonly class RegisterProduct')
            ->toContain('public function handle(): void');
    });

    it('generates final readonly invokable actions', function () {
        artisanCommand($this, 'module:make-action', ['name' => 'PublishProduct', 'module' => 'Widget', '--invokable' => true])
            ->assertSuccessful();

        $action = file_get_contents(base_path('tests/Fixtures/modules/Widget/app/Actions/PublishProduct.php'));

        expect($action)->toContain('final readonly class PublishProduct')
            ->toContain('public function __invoke(): void');
    });

    it('generates final readonly services without a handle method', function () {
        artisanCommand($this, 'module:make-service', ['name' => 'ProductService', 'module' => 'Widget'])
            ->assertSuccessful();

        $service = file_get_contents(base_path('tests/Fixtures/modules/Widget/app/Services/ProductService.php'));

        expect($service)->toContain('declare(strict_types=1)')
            ->toContain('final readonly class ProductService')
            ->not->toContain('handle');
    });

    it('maps helpers to the Support layer as final classes', function () {
        artisanCommand($this, 'module:make-helper', ['name' => 'StringHelper', 'module' => 'Widget'])
            ->assertSuccessful();

        $helper = file_get_contents(base_path('tests/Fixtures/modules/Widget/app/Support/StringHelper.php'));

        expect($helper)->toContain('declare(strict_types=1)')
            ->toContain('namespace Modules\Widget\Support;')
            ->toContain('final class StringHelper');
    });

    it('maps interfaces to app/Contracts', function () {
        artisanCommand($this, 'module:make-interface', ['name' => 'ProductContract', 'module' => 'Widget'])
            ->assertSuccessful();

        $interface = file_get_contents(base_path('tests/Fixtures/modules/Widget/app/Contracts/ProductContract.php'));

        expect($interface)->toContain('declare(strict_types=1)')
            ->toContain('namespace Modules\Widget\Contracts;')
            ->toContain('interface ProductContract');
    });

    it('maps resources to app/Http/Resources', function () {
        artisanCommand($this, 'module:make-resource', ['name' => 'ProductResource', 'module' => 'Widget'])
            ->assertSuccessful();

        $resource = file_get_contents(base_path('tests/Fixtures/modules/Widget/app/Http/Resources/ProductResource.php'));

        expect($resource)->toContain('declare(strict_types=1)')
            ->toContain('namespace Modules\Widget\Http\Resources;')
            ->toContain('class ProductResource extends JsonResource');
    });

    it('generates form requests in app/Http/Requests', function () {
        artisanCommand($this, 'module:make-request', ['name' => 'StoreProductRequest', 'module' => 'Widget'])
            ->assertSuccessful();

        $request = file_get_contents(base_path('tests/Fixtures/modules/Widget/app/Http/Requests/StoreProductRequest.php'));

        expect($request)->toContain('declare(strict_types=1)')
            ->toContain('class StoreProductRequest extends FormRequest')
            ->toContain('public function rules(): array');
    });

    it('generates middleware in app/Http/Middleware', function () {
        artisanCommand($this, 'module:make-middleware', ['name' => 'EnsureProductIsVisible', 'module' => 'Widget'])
            ->assertSuccessful();

        $middleware = file_get_contents(base_path('tests/Fixtures/modules/Widget/app/Http/Middleware/EnsureProductIsVisible.php'));

        expect($middleware)->toContain('declare(strict_types=1)')
            ->toContain('class EnsureProductIsVisible')
            ->toContain('public function handle(Request $request, Closure $next)');
    });

    it('generates attribute-based commands with handle(): int in app/Console/Commands', function () {
        artisanCommand($this, 'module:make-command', ['name' => 'SeedProducts', 'module' => 'Widget'])
            ->assertSuccessful();

        $command = file_get_contents(base_path('tests/Fixtures/modules/Widget/app/Console/Commands/SeedProducts.php'));

        expect($command)->toContain('declare(strict_types=1)')
            ->toContain('namespace Modules\Widget\Console\Commands;')
            ->toContain("#[Signature('command:name')]")
            ->toContain("#[Description('Command description.')]")
            ->toContain('public function handle(): int')
            ->toContain('return Command::SUCCESS;')
            ->not->toContain('protected $signature')
            ->not->toContain('getArguments');
    });

    it('maps mail to app/Mail', function () {
        artisanCommand($this, 'module:make-mail', ['name' => 'OrderShipped', 'module' => 'Widget'])
            ->assertSuccessful();

        $mail = file_get_contents(base_path('tests/Fixtures/modules/Widget/app/Mail/OrderShipped.php'));

        expect($mail)->toContain('declare(strict_types=1)')
            ->toContain('namespace Modules\Widget\Mail;')
            ->toContain('class OrderShipped extends Mailable');
    });

    it('generates factories in database/factories', function () {
        artisanCommand($this, 'module:make-factory', ['name' => 'Product', 'module' => 'Widget'])
            ->assertSuccessful();

        $factory = file_get_contents(base_path('tests/Fixtures/modules/Widget/database/factories/ProductFactory.php'));

        expect($factory)->toContain('declare(strict_types=1)')
            ->toContain('class ProductFactory extends Factory')
            ->toContain('public function definition(): array');
    });

    it('generates strict-typed migrations in database/migrations', function () {
        artisanCommand($this, 'module:make-migration', ['name' => 'create_products_table', 'module' => 'Widget'])
            ->assertSuccessful();

        $migrationFiles = glob(base_path('tests/Fixtures/modules/Widget/database/migrations/*_create_products_table.php'));

        if ($migrationFiles === false || $migrationFiles === []) {
            $this->fail('Migration file was not generated.');
        }

        $migration = file_get_contents($migrationFiles[0]);

        expect($migration)->toContain('declare(strict_types=1)')
            ->toContain('Schema::create');
    });

    it('generates API controllers returning SuccessResponse', function () {
        artisanCommand($this, 'module:make-controller', ['controller' => 'ProductController', 'module' => 'Widget', '--api' => true])
            ->assertSuccessful();

        $controller = file_get_contents(base_path('tests/Fixtures/modules/Widget/app/Http/Controllers/ProductController.php'));

        expect($controller)->toContain('declare(strict_types=1)')
            ->toContain('final readonly class ProductController extends Controller')
            ->toContain('use App\Http\Responses\SuccessResponse;')
            ->toContain('return new SuccessResponse(data: []);')
            ->toContain('status: Response::HTTP_CREATED')
            ->not->toContain('JsonResponse')
            ->not->toContain('response()->json');
    });

    it('generates invokable controllers returning SuccessResponse', function () {
        artisanCommand($this, 'module:make-controller', ['controller' => 'ProductShowController', 'module' => 'Widget', '--invokable' => true])
            ->assertSuccessful();

        $controller = file_get_contents(base_path('tests/Fixtures/modules/Widget/app/Http/Controllers/ProductShowController.php'));

        expect($controller)->toContain('final readonly class ProductShowController extends Controller')
            ->toContain('public function __invoke(Request $request): SuccessResponse')
            ->not->toContain('response()->json');
    });

    it('generates plain controllers as final readonly', function () {
        artisanCommand($this, 'module:make-controller', ['controller' => 'PlainProductController', 'module' => 'Widget', '--plain' => true])
            ->assertSuccessful();

        $controller = file_get_contents(base_path('tests/Fixtures/modules/Widget/app/Http/Controllers/PlainProductController.php'));

        expect($controller)->toContain('declare(strict_types=1)')
            ->toContain('final readonly class PlainProductController extends Controller');
    });
});
