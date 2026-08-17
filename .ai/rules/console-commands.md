---
paths:
  - 'stubs/module-generator/**, tests/Architecture/**, tests/Feature/Console/Commands/**'
---

# Console Commands

## Module scaffold stubs must stay backend-only and pass arch tests
Generated modules must boot without crashing. route-provider.stub guards mapWebRoutes/mapApiRoutes with file_exists checks, iterates apiroute.supported_versions, and wraps api routes with prefix('api/{version}') and name('api.{version}.{alias}.'), matching the uniform route-name contract api.{version}.{module}.{name}. All stubs (controller, seeder, event-provider, provider, config, routes) must include declare(strict_types=1) since the 'app uses strict types' arch rule scans Modules. controller.stub/controller-api.stub generate final readonly resource controllers (no create), matching the relaxed arch rules. Test dirs are lowercase (modules/*/tests) in phpunit.xml and tests/Pest.php. module:make tests need Process::fake() and json_decode helper with @return array<mixed, mixed> to satisfy phpstan.
