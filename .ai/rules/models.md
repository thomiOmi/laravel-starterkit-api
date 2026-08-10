---
paths:
  - 'modules/*/Models/**'
---

# Models

## Model attributes, ULID keys, and enum casts
Use PHP 8 attributes (#[Fillable], #[Hidden], #[UseFactory]) instead of $fillable/$hidden properties. ULID primary keys via HasUlids (UUID is not used). Cast enum columns with 'status' => StatusEnum::class. API response dates serialize as Y-m-d H:i:s (override/configure serialization). declare(strict_types=1) on every file. Models stay in the owning module (modules/*/Models); app/ shared types live in app/.
