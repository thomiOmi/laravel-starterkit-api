# ADR-0015: Media Storage as First Feature Module

- Status: Accepted
- Date: 2026-08-09

## Context

The roadmap required a first feature module to prove the modular architecture. Accounting was rejected (see ADR-0016); Media Storage has the highest reuse potential in real projects (avatars, documents, receipts, galleries).

## Decision

Add `Modules/Media`: model `Media` (ulid, disk, mime_type, size, path, meta, uploaded_by), endpoints upload/list/show/delete, permissions `media.view/create/delete`, `MediaPolicy`. Signed URLs are Laravel-native (disk `local` with `serve => true` + `ServeFile`), TTL 15 minutes.

## Consequences

- Proves the module pattern end-to-end (Actions, Controllers, Payloads, Requests, Resources) without new dependencies.
- Image processing is deliberately deferred (10MB free-typed limit; `intervention/image` recipe documented for later).
- Delete route deviates from IAM: `MediaPolicy::delete` allows owner OR permission, executed in the controller, not via `permission:` middleware.
- `uploaded_by` is a nullable ULID FK with `nullOnDelete`.
