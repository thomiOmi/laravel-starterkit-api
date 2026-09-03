# ADR-0035: Remove Variant Query Endpoint in Favor of Path Modifiers

- Status: Accepted
- Date: 2026-09-03

## Context

`GET /api/v1/media/{media}/variant?w=320&format=webp` was introduced in ADR-0031 as an on-the-fly variant endpoint with query parameters (`w` 32..2000, `format` webp|jpg). It coexisted with `GET /api/v1/media/{media}/s/{modifiers}` (`MediaModifier`) which uses path modifiers (`s_320,f_webp,q_80` or `s/320/f/webp`) plus `w/h/s/f/q/fit/kernel` support, readable cache (`w320-h200-f_webp-...`), and private `no-store` handling.

Maintaining two variant endpoints duplicates `MediaModifier` parsing, `MediaModifierController` logic, cache key generation (`xxh128`), and `variants/{id}/` storage. The query variant also lacks `fit`/`kernel` and readable cache.

## Decision

Remove the query variant completely:

- Delete `MediaVariantController` + `MediaVariantRequest` (`?w=`).
- Delete `MediaVariantTest` (9 cases) — superseded by `MediaModifierTest` (9 cases for `s/{modifiers}`).
- Keep only `GET /api/v1/media/{media}/s/{modifiers}` (`api.v1.media.modifier`, `where('modifiers','.*')`) as the canonical variant endpoint.
- Update `modules/Media/README.md` endpoint table and cURL examples (`?w=320` → `s/w_320,f_webp`).
- Remove mentions of `variant` query from `docs/architecture.md` and `docs/adr/0031`.

After this, `GET .../variant?w=320` returns `404` (route not defined) — intentional breaking change for a starterkit with no important data (fresh DB).

## Consequences

- Single source of truth for variants: `MediaModifier` with `s,w,h,f,q,fit,kernel` and IPX-style parsing (`w_320,h_200` + slash `s/320/f/webp` both supported).
- Simplified `Media` cache invalidation (single `variants/{id}/` directory per media).
- Tests drop from 63 to 62 Media tests (one file removed) but coverage for modifiers remains via `MediaModifierTest`.
- Clients using `?w=` must migrate to `s/{modifiers}` (e.g., `?w=320` → `/s/w_320`, `?w=320&format=webp` → `/s/w_320,f_webp`).
