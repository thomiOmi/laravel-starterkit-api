# ADR-0047: Media Readiness Decisions for 1.0.0

- Status: Accepted
- Date: 2026-11-25

## Context

The owner wants the Media module ready for use before 1.0.0. Contract changes made now are cheap; the same changes after the release are breaking. The goal is not parity with Spatie Media Library but the subset an adopter of a starter kit needs: upload, validation, storage, type classification, presentation, deletion, authorization, and lifecycle events for images, documents, video, and audio. Derived per-type files, range streaming, and external binaries stay out of the 1.0.0 scope.

Media already accepts more than images. `MediaUploadRequest` applies `required`, `file`, `max:` from `media.max_size`, `AllowedFileName`, `DetectedMimeType`, and `ImageDimensionLimits`; the `extensions:` rule is added only when `media.allowed_extensions` is an array, and the shipped value is `null`. `DetectedMimeType` calls `MediaMimeType::detect()` on the real bytes and rejects blocked markup/script/executable content and positive extension-to-MIME mismatches. `DisallowedExtensions` rejects script and executable name segments. Uploads use generated names, public media is served through `Storage::url()`, and private media through a signed `api.v1.media.file` route. Images additionally get EXIF-oriented WebP normalization, eager conversions, responsive siblings, an on-demand modifier endpoint, and a queue path.

What is missing is classification and per-type limits. `Media` stores only `mime_type` and `collection_name`; there is no type column or type enum, and `MediaMimeType::isImage()` is the only type-like helper. `MediaResource` exposes `mime_type` but no consumer-facing type. `media.max_size` is one number for every file kind, so a realistic video is rejected even when its content is valid.

Three contracts need a decision before the release:

- `Media::getTemporaryUrl()` accepts an optional conversion name but returns a signed URL for the **original** file; its own comment states that the signed file route serves the original only. A repository-wide search of `getTemporaryUrl(` and `signedUrl(` found no caller that passes a conversion argument to `Media::getTemporaryUrl()`. The only two-argument call is `DefaultUrlGenerator::getTemporaryUrlForMinutes()`, which invokes the generator interface method with a media and an expiration, not a conversion. Trait and controller callers of `signedUrl()` pass only a TTL.
- `MediaObserver::deleting()` calls `MediaFileRemover::removeAllFiles()` before `DeleteMediaAction::handle()` executes `$media->delete()`; the action does not wrap the delete in a transaction. If the row deletion fails after storage cleanup, the row survives while its files are gone.
- `MediaFileController::__invoke()` returns `$disk->response(...)`, which builds a `StreamedResponse` whose callback calls `fpassthru()` on the whole stream. Neither the controller nor the Laravel `FilesystemAdapter::response()` implementation handles `Accept-Ranges`, `Content-Range`, or `206 Partial Content`; the Laravel 13.x Responses documentation describes `file()` and `stream()` responses but does not claim Range support. This is a code-based finding, not an HTTP-level verification, so the runtime behavior is recorded with Medium confidence.

Official documentation used for these decisions: Laravel 13.x Eloquent Observers, section "Observers and Database Transactions" (`ShouldHandleEventsAfterCommit`) at https://laravel.com/docs/13.x/eloquent#observers; Laravel 13.x Filesystem (temporary URLs, visibility, downloaded files) at https://laravel.com/docs/13.x/filesystem; Laravel 13.x Responses (file responses, streamed responses) at https://laravel.com/docs/13.x/responses. ADR-0007 and its 2026-11-24 correction define enum placement, ADR-0013 defines native enum labels, ADR-0045 defines the four public seams, and ADR-0030, ADR-0031, ADR-0032, ADR-0035, ADR-0036, ADR-0037, ADR-0038, ADR-0040, and ADR-0041 define the Media behavior being preserved.

## Decision

### D1: Type classification is derived from MIME, not stored in a new column

Add `Modules\Media\Enums\MediaType` in `modules/Media/app/Enums/` with the TitleCase cases `Image`, `Document`, `Video`, `Audio`, and `Other`. The name follows the `MediaProcessingStatus` precedent: module enums here omit the `Enum` suffix, and no project rule requires one. Placement follows ADR-0007 and its correction: the enum is consumed only by Media, so it belongs in the owning module under `app/Enums`, not in `app/Enums` at the root.

The type is derived from the MIME detected from file contents by `MediaMimeType::detect()` (the same source used by `DetectedMimeType`), never from a client-supplied header. Mapping: `image/*` -> `Image`, `video/*` -> `Video`, `audio/*` -> `Audio`, and `application/pdf`, common office document MIME types, `text/plain`, and `text/csv` -> `Document`; everything else -> `Other`. The exact office MIME list is an implementation detail of the PR that adds the mapping. `MediaResource` gains an additive `type` field carrying the enum value as a string, so a consumer does not need to import a module enum; the existing seams from ADR-0045 remain unchanged.

Rejected: a stored `type` column plus a migration. It would make filtering and indexing easier, but nothing before 1.0.0 needs a database query by type. That option can be revisited with a new decision if a real filter or index requirement appears.

### D2: Per-type size limits override one global default

Keep `media.max_size` as the global default. Add an optional `media.max_size_by_type` config map keyed by the `MediaType` value and expressed in kilobytes, consistent with the `max:` validation rule. Resolution order: the per-type value when the map has an entry for the detected type, otherwise `media.max_size`. The type comes from the content-detected MIME used by D1, not from the upload header.

This ADR does not fix any concrete limit. The owner chooses the numbers in the implementation PR, with a permissive value for video and audio so the default does not reject ordinary uploads.

### D3: Remove the conversion argument from `Media::getTemporaryUrl()`

`Media::getTemporaryUrl()` will accept only the expiration. The current optional `$conversion` argument is removed because it returns a signed URL for the original file, which is misleading: the original may still carry EXIF data, and there is no signed-conversion route to serve a derivative. `Media::signedUrl()` and the public `api.v1.media.file` route are unchanged.

The alternative of building a signed-conversion route is rejected: no production or test caller passes a conversion argument today, so the route would add public surface without a consumer. `getTemporaryUrl()` is a public method on the `Media` model that adopters may call, so narrowing its signature is an intentional breaking change accepted only because it happens before 1.0.0. Under PHP semantics, an extra positional argument to a userland method is ignored at runtime but reported by PHPStan as `arguments.count`, while an unknown named argument raises an `Error`; neither form occurs in this repository.

### D4: Delete the row first, then remove files after commit

Deletion order is part of the contract: the `media` row is removed first, and storage cleanup happens only after the database change is committed. Before the row is deleted, the implementation collects every path it will need, including conversion paths, because `media_conversions` rows are removed by the foreign key cascade and would otherwise no longer be readable at cleanup time. The exact mechanism - an after-commit observer hook, an explicit post-commit cleanup step in the action, or another Laravel-native option - is chosen in the implementation PR using the Eloquent documentation cited above. `ShouldHandleEventsAfterCommit` exists, but the PR must verify its timing against the `deleting` event rather than assume it applies unchanged.

The preferred failure mode is an orphaned file that `media:cleanup` can report, not a row that points at a missing file. Event payloads and route contracts for delete stay the same.

### D5: Deliberate 1.0.0 limitations, deferred to 1.x

These are capability limits, not contract defects, and are documented as such before the release:

- No derived files for non-image media: no PDF thumbnails, video posters, transcoding, or audio waveforms. They require ghostscript, ffmpeg, or comparable binaries and would widen the deployment surface.
- AVIF and HEIC are accepted but not EXIF-normalized: they are outside `UploadMediaAction::PROCESSABLE_MIMES`, so they are stored as received. This is stated in the adopter documentation rather than silently assumed.
- Range requests and seeking are not supported by the signed file route. The current `Storage::response()` path streams the full body; implementing byte ranges is 1.x work.
- Very large uploads using chunked or direct-to-storage flows are out of scope.

## Consequences

- `MediaResource` gains a `type` field; existing fields and the response envelope are unchanged.
- `media.max_size_by_type` is a new optional config key; installs that omit it keep today's behavior. PR-2 sets the actual values after owner approval.
- `Media::getTemporaryUrl()` has a narrower signature on a public model method. After the change, a caller that still passes an extra positional argument does not fail at runtime but is flagged by PHPStan's `arguments.count`; a caller using an unknown named argument raises an `Error`. The change is made before 1.0.0 to remove the misleading contract while those failures are still acceptable.
- Delete may leave an orphan file on a failed or rolled-back transaction. The current `MediaCleanupCommand` scans only `media.disk` for orphans, so orphan files on `media.private_disk` are neither reported nor removed; its separate "DB record without file" check resolves each record's own `disk`, so private media is not falsely reported as missing. D4 therefore depends on extending orphan scanning to both disks before that ordering is delivered.
- The database schema, module routes, permissions, event names, and public seams are unchanged by this ADR.
- Follow-up work: PR-1 adds the enum, mapping, and resource field; PR-2 adds per-type limits and format tests; PR-3 handles the delete ordering and signature cleanup and extends `media:cleanup` orphan scanning to `media.private_disk`, because D4 relies on orphan detection on both disks; P7 documents the D5 limitations and the deployment constraints from D2 (PHP `upload_max_filesize`, `post_max_size`, web server body limits, worker memory).
- Review this decision when a real need for database filtering by type appears, when a signed conversion URL gains a consumer, when Laravel introduces first-party Range-aware file responses, or when external media binaries become an accepted deployment dependency.
