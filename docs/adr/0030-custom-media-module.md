# ADR-0030: Custom Media Module Inspired by Spatie Media Library

- Status: Accepted
- Date: 2026-08-23

## Context

The kit previously shipped the `spatie/laravel-medialibrary` package inside a
`modules/Media` scaffold, but the whole media stack was removed in
e1507e7/055327b to keep the starter lean. IAM still consumes an
`AvatarResolver` contract that degrades gracefully when no implementation is
bound, and `media.*` permissions already exist in `PermissionEnum`.

We need user-uploaded media (avatars first) without re-introducing a heavy,
image-processing dependency chain.

## Decision

Build a **custom, lightweight `Media` module** inspired by Spatie Media
Library's concepts rather than installing the package:

- Adopt now: `collection_name`, json `meta` (custom-properties equivalent),
  original-name tracking via meta, ULID primary key, file-adder behaviour in
  `UploadMediaAction`.
- Defer: polymorphic attachments (`model_type/model_id` + `HasMedia`),
  conversions/thumbnails and responsive images, signed private URLs, events.

Schema: ULID id, indexed `collection_name`, disk, mime_type, size, unique
path, visibility (`MediaVisibilityEnum`), nullable json meta, nullable
`uploaded_by` FK to users with nullOnDelete. V1 is owner-scoped; tenancy
classification is deferred until global multi-tenancy lands.

Endpoints live under `api/v1/media` guarded by the existing `media.*`
permissions; owners may view/delete their own uploads without extra grants.
The module binds `MediaAvatarResolver` as the `AvatarResolver` singleton so
the IAM profile flow works with zero changes on the consumer side.

## Consequences

- No new composer dependencies; storage uses the configured public disk.
- Regular users gain `media.view/create` through `IAMSeeder` so they can
  upload their own avatars; staff keep full `media.*` control.
- Future Spatie-style features can be layered onto this schema without
  breaking changes because collections and meta are already first-class.
