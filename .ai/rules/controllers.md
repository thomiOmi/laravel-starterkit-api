---
paths:
  - 'modules/*/Controllers/**'
---

# Controllers

## Invokable final readonly controllers with response contract
Controllers are single-action invokable classes: final readonly, extend the module or app base Controller, __invoke(Request $request): JsonResponse. Keep them thin: delegate business logic to Actions via ->handle(), never build queries inline. Return SuccessResponse / ProblemResponse (RFC 9457) from app/Http/Responses; no 'success' boolean. Date fields use Y-m-d H:i:s. Follow existing sibling controllers for structure (e.g. modules/IAM/Controllers).
