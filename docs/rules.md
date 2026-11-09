# Core Rules

The short list of rules that keep this kit safe and coherent.

Layers: tests and tools are the enforcement truth, `.ai/rules/*` holds the rule text, and ADRs hold the reasoning; this page only links to them and does not restate the rules.

## Enforced

| Rule | Enforced by | Why (ADR) |
|---|---|---|
| [Module boundaries: cross-module references only through the public seams](../.ai/rules/contracts.md) | `modules should be isolated`, `modules only reference other modules through public layers` | [ADR-0045](adr/0045-module-boundaries.md) |
| [App contracts are interfaces](../.ai/rules/contracts.md) | `app contracts should be interfaces` | - |
| [Models extend Eloquent Model, are not final, and carry no Model suffix](../.ai/rules/models.md) | `app models should extend Eloquent Model`, `module models should extend Eloquent Model`, `models should not be final`, `app models should not have Model suffix` | - |
| [Models stay out of Form Requests and Payloads](../.ai/rules/models.md) | `module models should not be used in form requests`, `module models should not be used in payloads` | - |
| [Actions are final readonly, suffixed Action, with only __construct and handle](../.ai/rules/actions.md) | `module actions are final and readonly`, `module actions should have handle method`, `module actions should only have construct and handle`, `module actions should have Action suffix` | - |
| [Actions stay free of HTTP concerns](../.ai/rules/actions.md) | `module actions should not use HTTP Request`, `module actions should not use abort helpers` | - |
| [Controllers are final readonly, suffixed Controller, and do not use models](../.ai/rules/controllers.md) | `module controllers are final and readonly`, `module controllers should have Controller suffix`, `module controllers should not use Model` | [ADR-0009](adr/0009-plain-abstract-readonly-controller.md) |
| [Services are final readonly, suffixed Service, and do not use HTTP Request](../.ai/rules/services.md) | `module services are final and readonly`, `module services should have Service suffix`, `module services should not use HTTP Request` | - |
| [Payloads are final readonly, suffixed Payload, and define toArray](../.ai/rules/payloads.md) | `module payloads are final and readonly`, `module payloads should have Payload suffix`, `module payloads should have toArray method` | - |
| [Builders extend BaseQueryBuilder](../.ai/rules/builders.md) | `module builders should extend BaseQueryBuilder` | - |
| [Resources extend JsonResource](../.ai/rules/resources.md) | `module resources should extend JsonResource` | - |
| [Requests extend FormRequest with rules, suffixed Request, and list requests extend PaginationRequest](../.ai/rules/requests.md) | `app http requests should extend FormRequest and have rules`, `app http requests should have Request suffix`, `module requests should extend FormRequest and have rules`, `module requests should have Request suffix`, `module list requests extend PaginationRequest` | - |
| [Policies are suffixed Policy and are final](../.ai/rules/policies.md) | `app policies should have Policy suffix`, `module policies should have Policy suffix`, `module policies should be final` | - |
| [Providers extend ServiceProvider and are suffixed ServiceProvider](../.ai/rules/providers.md) | `app providers should extend ServiceProvider`, `module providers should extend ServiceProvider`, `app providers should have ServiceProvider suffix` | [ADR-0029](adr/0029-nwidart-laravel-modules.md) |
| [Commands extend Command, are suffixed Command, and define handle](../.ai/rules/commands.md) | `app commands should extend Command and have handle`, `app commands should have Command suffix` | - |
| [Notifications extend Notification](../.ai/rules/notifications.md) | `app notifications should extend Notification` | - |
| [Seeders define run and never call another module's seeder](../.ai/rules/database.md) | `seeders should have run method`, `module seeders should have run method`, `module seeders do not call seeders from other modules` | [ADR-0020](adr/0020-single-iam-seeder.md) |
| [Factories extend Factory and define definition](../.ai/rules/database.md) | `module factories should extend Factory`, `module factories should have definition method` | - |
| [Enums live only in Enums namespaces](../.ai/rules/enums.md) | `app enums should be enums`, `app classes should not be enums outside App\Enums` | [ADR-0007](adr/0007-iam-enums-in-app.md) |
| [App concerns are traits](../.ai/rules/concerns.md) | `app concerns should be traits` | - |
| [env() is used only in config files](../.ai/rules/config.md) | `env should only be used in config` | - |
| [App middleware define handle and carry no Middleware suffix](../.ai/rules/middleware.md) | `app http middleware should have handle method`, `app http middleware should not have Middleware suffix` | - |
| [App responses implement Responsable](../.ai/rules/responses.md) | `app http responses should implement Responsable` | - |
| [Modules follow the required folder layout and name](../.ai/rules/modules-structure.md) | `module scaffolds follow the kit folder structure`, `module folder name matches module.json name field exactly` | [ADR-0029](adr/0029-nwidart-laravel-modules.md) |
| [Module dependencies are declared and valid](../.ai/rules/modules-structure.md) | `module dependencies are declared in module.json`, `module dependencies pass validation (installed, enabled, acyclic)`, `modules.core config key is explicitly defined` | [ADR-0045](adr/0045-module-boundaries.md), [ADR-0029](adr/0029-nwidart-laravel-modules.md) |
| [Core modules do not depend on business modules](../.ai/rules/modules-structure.md) | `core modules do not depend on business modules` | [ADR-0029](adr/0029-nwidart-laravel-modules.md) |
| [Unit tests do not use the database and tests do not use PHPUnit assertions](../.ai/rules/tests.md) | `unit tests do not use the database`, `tests should not use PHPUnit assertions` | - |
| [Used translation keys exist](../.ai/rules/lang.md) | `phpstan` | - |

## Convention only

Selected with one criterion: violating the rule causes a bug, a security hole, or architectural damage, not a style issue.

| Rule | Enforced by | Why (ADR) |
|---|---|---|
| [Multi-step writes are wrapped in a transaction](../.ai/rules/actions.md) | convention (not enforced) | - |
| [Validation lives in Form Requests, not actions or controllers](../.ai/rules/requests.md) | convention (not enforced) | - |
| [Cross-module behaviour uses contracts in app/Contracts](../.ai/rules/contracts.md) | convention (not enforced) | [ADR-0045](adr/0045-module-boundaries.md) |
| [lang/en and lang/id keep the same key set](../.ai/rules/lang.md) | convention (not enforced) | - |
| [Exception mapping to ProblemResponse happens only in the handler](../.ai/rules/error-handling.md) | convention (not enforced) | - |
| [Bulk endpoints use BulkActionRequest](../.ai/rules/bulk-actions.md) | convention (not enforced) | - |
| [Module providers extend the nwidart ModuleServiceProvider](../.ai/rules/providers.md) | convention (not enforced) | [ADR-0029](adr/0029-nwidart-laravel-modules.md) |
| [Module activation is driven by modules_statuses.json](../.ai/rules/config.md) | convention (not enforced) | [ADR-0029](adr/0029-nwidart-laravel-modules.md) |
| [Route middleware stays explicit on the route group](../.ai/rules/routes.md) | convention (not enforced) | - |
| [Feature flags come from config or Pennant, never env](../.ai/rules/features.md) | convention (not enforced) | [ADR-0022](adr/0022-feature-flag-middleware.md) |
