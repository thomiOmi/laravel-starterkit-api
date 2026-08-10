---
paths:
  - 'database/migrations/**, modules/*/Database/**'
---

# Database

## Migration conventions: enum defaults, no chaining, factories and seeders
Migrations: use Enum value as column default ($table->string('status')->default(StatusEnum::Pending->value)); cast the column to the Enum in the model. Never chain migration commands with && or ; (identical timestamps). Modules own their schema in modules/*/Database (Migrations, Factories, Seeders); create factories and seeders for every new model. Do not edit database/ or modules/*/Database without approval — schema changes are a review gate.
