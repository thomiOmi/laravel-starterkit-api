---
paths:
  - 'modules/*/Routes/**'
---

# Routes

## Single route discovery, explicit middleware, route names
All module routes are discovered by the app RouteServiceProvider (modules/{Module}/Routes/V1.php); do not register routes elsewhere. Base prefix is api/{version}/{module}, route names follow v1.{module}.{name} (e.g. v1.iam.register). Keep middleware explicit on the route group (auth:sanctum, throttle, permission) — no hidden middleware in service providers. Routes live in the module (feature routes) while shared/global middleware belongs to the app.
