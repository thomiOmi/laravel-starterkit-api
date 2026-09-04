# ADR-0037: Media Opsi B Full Replacement ULID collection/file_name + Structure Cleanup

- Status: Accepted
- Date: 2026-09-03

## Context

PR #293 `feat/media-full-ulid-name-filename` migrates `media` from `path` FK to polymorphic ULID `collection/file_name` virtual path. User draft proposes Support subfolders `PathGenerator/FileNamer/FileRemover/UrlGenerator`, DRY `MediaModifier` into `MediaConversion`, removing `media_conversions` table, aligning `media` schema 1:1 Spatie `id() + uuid nullable`, and removing `config.collections/conversions` in favor of model-driven `registerMediaCollections`. Exploration shows current `Support` has 6 files (`DefaultPathGenerator`, `FileAdder`, `MediaCollection`, `MediaConversion` value object, `MediaModifier`, `PendingMedia`), `media_conversions` holds eager `thumbnail/medium/large` rows (`MediaConversion` Eloquent), and `config` holds `collections {avatars single_file}` + `conversions {thumbnail 320 webp}`. Need decision on folder shape, DRY boundary, table retention, PK type, and config removal.

## Decision

1. **PK keep ULID primary** (`ulid()->primary()` + `nullableUlidMorphs`) — not Spatie `id() + uuid nullable`. PR #293 Opsi B already `migrate:fresh` with ULID, `no uuid/findByUuid`, `timestamps()` not `nullableTimestamps()`. Keep lean.
2. **Path virtual `collection/file_name` (Opsi A PathGenerator)** — `DefaultPathGenerator::getPath()` = `collection_name/file_name`, `getPathForConversions` via `dirname`. No `media.path` column.
3. **Folder cleanup minimal** — create `Support/PathGenerator`, `Support/UrlGenerator`, `Support/FileNamer` subfolders with `MediaPathGenerator/MediaUrlGenerator/MediaFileNamer` interfaces + `Default*` implements. Keep `Contracts\PathGenerator` for DI, alias Support interface if needed. Defer `Downloaders/ResponsiveImages/FileRemover/Conversions` until `addMediaFromUrl/withResponsiveImages` needed. Fix typo `MediaPathGenator`.
4. **DRY `MediaModifier` into `MediaConversion` value object** — delete `Support/MediaModifier.php`, add `Support/MediaConversion::fromModifiers(string $modifiers): self` parsing `w_320,f_webp` + `s/320` both-style + `toCacheKey()`. One value object for on-demand `variants/{id}/{hash}` and eager `conversions/{id}/{name}.webp`.
5. **Keep `media_conversions` table** — on-demand variants are file-cache only, eager conversions are DB-tracked `hasGeneratedConversion()`. Keep `2026_08_30_000001_create_media_conversions_table.php`.
6. **Remove `config.collections`/`config.conversions`** — keep `media.disk` + `queue` + `mimes`/`max_size`. `UploadMediaAction::resolveVisibility()` + `isSingleFile()` + `MediaConversionService::generate()` switch to model-driven `$owner->getMediaCollection($collection)` + `registerMediaConversions()`, fallback default `private/false` and `[]`.

## Consequences

- One PR `feat/media-structure-cleanup` after #293: move 3 interfaces, delete `MediaModifier`, add `fromModifiers()`, delete `config.collections/conversions`, update `UploadMediaAction`/`MediaConversionService` to model-driven. `pint` + `types:check` 0, `migrate:fresh`, 628 tests pass.
- Eager conversions stay DB-tracked, on-demand stays file-cache, both share `MediaConversion` value object — less duplication, clearer boundary.
- No PK migration churn — ULID stays, `avatar` flow via `collection/file_name` survives `migrate:fresh`.
