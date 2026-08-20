---
paths:
  - 'stubs/module-generator/**, tests/Architecture/**, tests/Feature/Console/Commands/**'
---

# Console Commands

## Module scaffold stubs must stay backend-only and pass arch tests
Generated modules must boot without crashing. route-provider.stub guards mapWebRoutes/mapApiRoutes with file_exists checks, iterates apiroute.supported_versions, and wraps api routes with prefix('api/{version}') and name('api.{version}.{alias}.'), matching the uniform route-name contract api.{version}.{module}.{name}. All PHP stubs (controller, seeder, event-provider, provider, config, routes, and every layer stub) must include declare(strict_types=1) since the 'app uses strict types' arch rule scans Modules. controller.stub/controller-api.stub generate final readonly resource controllers returning SuccessResponse (never JsonResponse), and controller.invokable/controller-plain are final readonly too. model.stub is attribute-based (#[Fillable], #[Hidden], #[UseFactory], commented until needed) and non-final; command.stub uses #[Signature]/#[Description] with handle(): int; action/action-invoke/service/service-invoke stubs are final readonly; helper/helper-invoke stubs are final static-utility classes (helpers map to app/Support via config). Generator paths in config/modules.php: interfaces -> app/Contracts, emails -> app/Mail, resource -> app/Http/Resources, command -> app/Console/Commands, lang -> lang, helpers -> app/Support; scopes generate to app/Models/Scopes (namespace derived from model path - do not move the path). Test dirs are lowercase (modules/*/tests) in phpunit.xml and tests/Pest.php. module:make tests need Process::fake() and json_decode helper with @return array<mixed, mixed> to satisfy phpstan.
