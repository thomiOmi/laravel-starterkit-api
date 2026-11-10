# Media Module

Polymorphic media library: one `media` table stores files for any owning model, plus image conversions, responsive variants, and signed streaming. Status: core module (`config/modules.php:29`), declares no module dependencies (`modules/Media/module.json:7`); IAM depends on it (`modules/IAM/module.json:7`).

## Layers

| Layer | PHP files | Responsibility |
| --- | --- | --- |
| Actions | 7 | upload, delete, reorder, reprocess, eager/on-demand conversion, responsive images |
| Builders | 1 | query allow-lists and searchable columns (`app/Builders/MediaBuilder.php:16-52`) |
| Console | 2 | `media:cleanup`, `media:reprocess` |
| Contracts | 1 | `HasMedia` seam (`app/Contracts/HasMedia.php:23`) |
| Enums | 2 | processing status, visibility |
| Events | 5 | created, uploaded, processed, processing failed, deleted |
| Http | 10 | 6 V1 controllers, 3 V1 form requests, 1 API resource |
| Jobs | 1 | `ProcessMediaJob` |
| Models | 2 | `Media`, `MediaConversion` |
| Observers | 1 | storage cleanup on delete (`app/Observers/MediaObserver.php:12-16`) |
| Payloads | 1 | `MediaUploadPayload` |
| Policies | 1 | `MediaPolicy` |
| Providers | 3 | module, route, event wiring |
| Rules | 3 | `AllowedFileName`, `DetectedMimeType`, `ImageDimensionLimits` |
| Services | 1 | `MediaConversionService` |
| Support | 22 | path, naming, URL, download, removal, scanning, detection, value objects |
| Traits | 1 | `InteractsWithMedia` |

Total: 64 PHP files under `modules/Media/app`.

## Public seams and owner usage

Cross-module references are allowed only into `Contracts`, `Models`, `Traits`, and `Rules` (ADR-0045).

- `HasMedia` contract: morph relation `media()` (`app/Contracts/HasMedia.php:30`), fluent `addMedia()` (`:69`), `addMediaFromRequest()` (`:82`), `addMediaFromUrl()` (`:99`), reads `getMedia()` (`:106`) and `getFirstMedia()` (`:108`), URL helper `getFirstMediaUrl()` (`:76`), ordering `reorderMedia()` (`:119`), model registration hooks (`:36`, `:41`).
- `InteractsWithMedia` implements the contract: `media()` (`app/Traits/InteractsWithMedia.php:31`), `addMedia()` (`:46`), `addMediaFromRequest()` (`:53`), `addMediaFromUrl()` (`:93`), `addMediaFromString()` (`:100`), `getMedia()` (`:112`), `getFirstMediaUrl()` (`:128`), `getFirstMediaSignedUrl()` (`:143`), `clearMediaCollection()` (`:221`), `reorderMedia()` (`:240`), `registerMediaCollections()` (`:285`), `registerMediaConversions()` (`:293`), `addMediaCollection()` (`:304`), `addMediaConversion()` (`:312`).
- `Media` model: polymorphic owner `model()` (`app/Models/Media.php:89`), uploader `uploadedBy()` (`:99`), conversions `conversions()` (`:209`), ownership check `belongsToModel()` (`:111`), visibility check `isPublic()` (`:133`). `MediaConversion::media()` (`app/Models/MediaConversion.php:39`).

Owner example: `User` implements the contract (`modules/IAM/app/Models/User.php:60`) and uses the trait (`:69`). It registers a single-file public `avatars` collection accepting image types (`:111-114`) and a `default` collection (`:116`), plus `thumbnail` 320x320 cover webp q80 (`:121-127`) and `medium` width 1024 webp q85 for avatars and default (`:129-133`).

Boundary rule: Media must not import IAM (ADR-0045). No production file under `modules/Media` references `Modules\IAM`.

## Storage and URLs

- Disk selection: public media uses `media.disk` (default `public`), everything else uses `media.private_disk` (default `local`) (`app/Actions/UploadMediaAction.php:530-540`); explicit mismatches between disk and visibility are rejected (`:454-480`).
- Path layout: original `{media.prefix}/{collection_name}/{file_name}` (`app/Support/PathGenerator/DefaultPathGenerator.php:12-15`, `app/Support/MediaPrefix.php:50-53`); eager conversions under `conversions/{id}` (`:17-20`); on-demand cache under `conversions/derived/{id}` (`:22-25`); responsive siblings under `{original dir}/responsive-images` (`:27-30`). The path generator owns all four directories (`app/Support/PathGenerator/MediaPathGenerator.php:14-29`) and per-model overrides come from `media.custom_path_generators` (`app/Models/Media.php:359-382`).
- Upload flow: guards run first (`app/Actions/UploadMediaAction.php:59-64`); supported images are re-encoded, other files are stored unchanged (`:66-80`); `MediaUploaded` and `MediaCreated` fire before conversion work is dispatched (`:91-94`).
- Eager conversions: `MediaConversionService::generate()` skips non-image media and logs per-conversion failures without aborting the rest (`app/Services/MediaConversionService.php:30-62`).
- On-demand modifiers: cache key and ETag derive from version, id, modifiers, and format (`app/Http/Controllers/V1/MediaModifierController.php:52-54`), `If-None-Match` returns 304 (`:56-60`), generation is guarded by a 60 second lock with a 10 second block (`:82-83`), public responses get max-age while private ones get `private, no-store` (`:72-79`).
- Responsive images: target widths come from `media.responsive.widths` and never exceed the source width (`app/Actions/GenerateResponsiveImagesAction.php:20-21`, `:111-133`).
- URLs: `Media::url()` returns null for private media and resolves conversions through the conversion disk (`app/Models/Media.php:246-273`); `signedUrl()` signs route `api.v1.media.file` (`:285-294`); `DefaultUrlGenerator` tries the disk temporary URL and falls back to the signed route for local disks (`app/Support/UrlGenerator/DefaultUrlGenerator.php:27-45`); `media.version_urls` appends `?v={timestamp}` (`app/Models/Media.php:387-397`); `getSrcset()` builds `w` descriptors from generated siblings (`:409-438`).

## HTTP surface

Base path `api/v1/media`, route names prefixed `api.v1.media.` (`app/Providers/RouteServiceProvider.php:77-80`).

| Method | Path | Middleware | Handler |
| --- | --- | --- | --- |
| POST | `/api/v1/media` | `auth:sanctum`, `active`, `throttle:api`, `permission:media.create` (`modules/Media/routes/V1.php:22-25`) | `MediaUploadController` with `MediaUploadRequest` |
| GET | `/api/v1/media` | `auth:sanctum`, `active`, `throttle:api` (`modules/Media/routes/V1.php:27`) | `MediaListController`, scoped to the current user as owner (`app/Http/Controllers/V1/MediaListController.php:28-30`) |
| GET | `/api/v1/media/{media}` | `auth:sanctum`, `active`, `throttle:api` (`modules/Media/routes/V1.php:29`) | `MediaShowController`; `?expires=1..1440` swaps in a signed URL (`app/Http/Requests/V1/MediaShowRequest.php:31`, `app/Http/Controllers/V1/MediaShowController.php:33-36`) |
| GET | `/api/v1/media/{media}/s/{modifiers}` | `auth:sanctum`, `active`, `throttle:api` (`modules/Media/routes/V1.php:30`) | `MediaModifierController` |
| DELETE | `/api/v1/media/{media}` | `auth:sanctum`, `active`, `throttle:api` (`modules/Media/routes/V1.php:31`) | `MediaDeleteController` calling `DeleteMediaAction` |
| GET | `/api/v1/media/{media}/file` | `signed`, `throttle:api` (`modules/Media/routes/V1.php:16-17`) | `MediaFileController`; no bearer token, the signature is the credential |

Authorization lives in `MediaPolicy` (`app/Policies/MediaPolicy.php:22-74`): view allows public media, the owning model, the uploader, or `media.view` (`:29-41`); delete allows the owner, the uploader, or `media.delete` (`:47-58`); update has no permission and no route exposes it (`:60-74`). The policy is bound with `#[UsePolicy]` (`app/Models/Media.php:61`).

Permissions are `media.view`, `media.create`, `media.delete` (`app/Enums/PermissionEnum.php:25-27`), seeded for every enum case (`modules/IAM/database/seeders/IAMSeeder.php:55-57`); the `user` role receives view and create only (`:65-69`).

## Commands and queue

- `media:cleanup` (`app/Console/Commands/MediaCleanupCommand.php:16`): dry-run by default, deletes only with `--force` (`:22`); derived on-demand conversions are excluded from orphan detection (`:44-51`); it scans `media.disk` only (`:23-24`).
- `media:reprocess` (`app/Console/Commands/MediaReprocessCommand.php:14`): filters by `--id`, `--collection`, `--conversion`; `--conversion` is ignored with `--queued` and a warning is printed (`:37-41`).
- Both commands are registered by the module provider (`app/Providers/MediaServiceProvider.php:51-54`).
- `ProcessMediaJob` implements `ShouldQueue` (`app/Jobs/ProcessMediaJob.php:32`) with backoff 10/30, 60 second timeout, and 3 tries (`:29-31`). It marks the media processing, generates conversions, builds responsive images when wanted, then marks it processed (`:58-66`).
- Queueing happens when a queue name is resolved from the upload payload or `media.queue` (`app/Actions/UploadMediaAction.php:129-137`); `media.queue` defaults to false (`modules/Media/config/config.php:80`).
- Deletion removes files in the observer hook (`app/Observers/MediaObserver.php:12-16`), which calls `DefaultFileRemover::removeAllFiles()` for the original, each conversion, responsive images, and both conversion directories (`app/Support/FileRemover/DefaultFileRemover.php:13-29`). Conversion rows cascade with the media row (`modules/Media/database/migrations/2026_08_30_000001_create_media_conversions_table.php:15`).

## Known gaps

1. Media permissions and role grants are seeded by IAM, not by Media: `IAMSeeder` creates every `PermissionEnum` case (`modules/IAM/database/seeders/IAMSeeder.php:55-57`) and grants the `user` role view and create (`modules/IAM/database/seeders/IAMSeeder.php:64-69`); Media's own seeder has an empty `run()` (`modules/Media/database/seeders/MediaSeeder.php:14-17`). Separately, `media.delete` (`app/Enums/PermissionEnum.php:27`) is not granted to `user` (`modules/IAM/database/seeders/IAMSeeder.php:64-69`), so for a regular user deletion rests on ownership: `MediaPolicy::delete()` allows the owning model or the uploader before checking the permission (`app/Policies/MediaPolicy.php:47-58`).
2. `Media::getTemporaryUrl()` ignores the conversion argument and always signs the original file route (`app/Models/Media.php:296-305`).
3. `media:cleanup` orphan scanning covers only `media.disk` (`app/Console/Commands/MediaCleanupCommand.php:23-24`), so orphan files on `media.private_disk` are neither reported nor removed. The "DB record without file" check resolves each record through its own `disk` column, falling back to `media.disk` when that column is empty (`app/Console/Commands/MediaCleanupCommand.php:97-99`), so private media stored on `media.private_disk` is no longer falsely reported as missing.

## Related ADRs

- [ADR-0031: First-party image processing](../../docs/adr/0031-first-party-image-processing.md)
- [ADR-0032: Signed media URLs, events, cached variants](../../docs/adr/0032-signed-media-events-cached-variants.md)
- [ADR-0036: Media polymorphic squash](../../docs/adr/0036-media-polymorphic-squash.md)
- [ADR-0037: Collection and file name structure](../../docs/adr/0037-media-opsi-b-collection-filename-structure.md)
- [ADR-0038: Responsive images](../../docs/adr/0038-responsive-images.md)
- [ADR-0039: Path generator owns the original path only (superseded by ADR-0040)](../../docs/adr/0039-media-path-generator-owns-original-path-only.md)
- [ADR-0040: Path generator owns derivative directories](../../docs/adr/0040-media-path-generator-owns-derivative-directories.md)
- [ADR-0041: Upload original resolves through the path generator](../../docs/adr/0041-upload-original-writes-through-path-generator.md)
- [ADR-0045: Module boundaries](../../docs/adr/0045-module-boundaries.md)
