---
paths:
  - 'modules/*/Http/Resources/**'
  - 'modules/*/Resources/**'
---

# Resources

## Goal

API resource transformers in `modules/{Module}/Http/Resources/`. Resources belong to the module; the app-wide envelope shape is global.

## Rules

1. `extends JsonResource`, contract envelope via SuccessResponse
2. Date format `Y-m-d H:i:s`
3. Resources belong to the module; app-wide shape is global

## Forbidden

- No resource altering the global envelope structure
