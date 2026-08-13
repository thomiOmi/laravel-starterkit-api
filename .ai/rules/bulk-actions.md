---
paths:
  - 'modules/*/Http/Controllers/**'
  - 'modules/*/Controllers/**'
  - 'app/Http/Requests/BulkActionRequest.php'
---

# Bulk Actions

## Goal

Mass mutation endpoints (delete, restore) processing many ids at once. Shared request `App\Http\Requests\BulkActionRequest` (validates `ids` max 50 + `action`); the controller delegates to an Action; the Action executes a single bulk query.

## Rules

1. `BulkActionRequest` (shared) is mandatory for all bulk endpoints; per-action authorization via `authorize()` based on the route name
2. Bulk action = a single `whereIn` query (delete/restore), returns count
3. `Bus::bulk`/`Bus::batch` NOT used for synchronous mutations; only for heavy per-item processing that needs a queue (no use case yet; rule added when one appears)
4. Routing: `POST /{resource}/bulk/{action}`, route name `v1.{module}.{resource}.bulk.{action}`
5. Note: bulk queries do not trigger per-row model events/observers (deliberate trade-off)

## Forbidden

- No dispatching a job per item for simple delete/restore
- No query loops in controllers; loops (if any) only in Actions
