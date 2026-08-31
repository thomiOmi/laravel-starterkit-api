# ADR-0036: Media Polymorphic Squash, Trait, Conversions and Single-File Avatars

- Status: Accepted
- Date: 2026-08-30
- Supersedes: ADR-0030 (Custom Media Module)

## Context

ADR-0030 described a single-table `media` owned by `users` via `uploaded_by` FK, with `MediaUrlResolver` cross-module contract. The module was not reusable (`Post`/`Product`/`Organization` could not own media), `MediaVisibilityEnum` lived in `App\Enums` despite being Media-owned, and `avatars` single_file was hard-coded. Conversions were deferred to a separate `media_conversions` table, and `InteractsWithMedia` was missing.

The starterkit has no production data, so a squashed migration is acceptable.

## Decision

Squash `2026_08_23_create_media_table` to polymorphic final:

```php
$table->nullableUlidMorphs('model'); // model_type/model_id
$table->string('collection_name');
$table->string('disk'); $table->string('mime_type'); $table->unsignedBigInteger('size'); $table->string('path')->unique();
$table->string('visibility'); $table->string('original_name')->nullable(); $table->string('original_extension',20)->nullable();
$table->string('sha256',64)->nullable()->index(); $table->json('meta')->nullable(); $table->json('custom_properties')->nullable();
$table->unsignedInteger('order_column')->default(0);
$table->nullableUlidMorphs('uploaded_by'); // uploaded_by_type/id without FK
$table->index(['model_type','model_id','collection_name'], 'media_model_collection_index');
```

Add `media_conversions` (`ulid id`, `foreignUlid media_id` cascade, `name`, `disk`, `path`, `mime_type`, `size`, `etag`, unique[media_id,name]).

Move `MediaVisibilityEnum` `App\Enums` → `Modules\Media\Enums`, add `MediaStatus` (Pending/Processing/Ready/Failed).

`Media` now `morphTo model/uploadedBy`, `hasMany conversions`, `belongsToModel(Model)`, `url(?conversion)`, `signedUrl`, `ordered` scope. `MediaConversion` belongsTo `Media`.

Contracts `HasMedia` (`media():MorphMany<Media,Model>`), `MediaUrlGenerator` (`getUrl`, `getTemporaryUrl`), `MediaProcessor`; trait `InteractsWithMedia` (`media()`, `addMedia():PendingMedia`, `getMedia`, `getFirstMedia`, `getFirstMediaUrl/SignedUrl`, `clearMediaCollection`, `reorderMedia`) + `Support\PendingMedia` fluent (`usingName/withCustomProperties/toMediaCollection`). Services `MediaStorageService` + `MediaUrlGeneratorService` (singleton `MediaUrlGenerator`), `MediaConversionService` (`thumbnail`/`medium`/`large` via `Image::fromStorage`).

`UploadMediaAction` signature `handle(Payload, Model $owner, ?Model $uploader)`, config-driven `media.collections.*.{visibility,single_file}` + `media.conversions` + `media.queue` (`ProcessMediaJob` ShouldQueue). Single_file `avatars` uses `lockForUpdate` + `updateOrCreate` same id + `wasChanged('path')` cleanup of old file/`variants/{id}`. `order_column` for non-single_file = `max+1`. Dispatch `MediaUploaded` + `MediaCreated` + sync/queued conversions.

`DeleteMediaAction` deletes `conversions` dir + files, `ReorderMediaAction`/`GenerateConversionAction`/`AttachMediaAction` added.

`MediaPolicy` now `Identity $user` + `isPublic` or `belongsToModel` or `is(uploadedBy)` or `can`.

`Media*Controller` `Identity $currentUser` + `abort_unless Model`, `MediaResource` exposes `original_extension/sha256/custom_properties/order_column/conversions` + `url(?conversion)`.

Dependency inversion: `Media` no longer imports `Modules\IAM\Models\User` (independent, `requires []`), `IAM` `User implements HasMedia` + `InteractsWithMedia`, `IAM` `requires ["Media"]` (acyclic, verified via `ModuleDependencyCheck`), `UpdateProfileAction` uses `DB::table('media')` without `Modules\Media\` string, `IAM` tests still via `modules/Media`? No, Media tests now use `Spatie\Permission` + `DB` + `Str` to avoid `Modules\IAM\` string, so Media stays independent.

Remove `App\Contracts\MediaUrlResolver` + `Services\MediaUrlResolver`; URL via `Media::url()/signedUrl()` and `MediaUrlGeneratorService`.

Artisan `media:cleanup --dry-run` (orphan files vs `media`+`media_conversions`) and `media:reprocess --id/--collection/--queued`.

Config `phpstan.neon` adds `modules/Media/config` to `configDirectories`.

## Consequences

- Fresh installs require `migrate:fresh` (squashed). Existing envs with old `uploaded_by` FK must fresh or manual `dropForeign`.
- `Media` is now reusable: `$post->addMedia($file)->toMediaCollection('images')`, `$post->reorderMedia('images', [$id3,$id1])`.
- `avatars` single_file upsert keeps same ULID, old file/variants deleted, `order_column` stable.
- Conversions are synchronous by default (`MEDIA_QUEUE=false`) via `MediaConversionService`; set `MEDIA_QUEUE=true` for `ProcessMediaJob`.
- `Media` table no longer has FK to `users`; `uploaded_by` is morph without constraint, `model` is polymorphic without FK.
- `IAM` now depends on `Media`; `Media` tests must not import `Modules\IAM\` directly (use `DB`/`Spatie`).
- Variant on-the-fly `GET /variant?w=` remains (not yet replaced by IPX `s/...`), `conversions` are stored under `conversions/{id}/{name}.webp` separate from `variants`.
