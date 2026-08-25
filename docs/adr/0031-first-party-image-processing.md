# ADR-0031: First-Party Image Processing and On-The-Fly Variants

- Status: Accepted
- Date: 2026-08-26

## Context

ADR-0030 deferred conversions/thumbnails, responsive images, and signed
private URLs to keep the initial Media module lean. Since then Laravel
13.20 shipped the first-party `Illuminate\Image` API (backed by
Intervention Image v4, already installed) and Laravel 13.25 made image
instances directly returnable from routes (`Responsable`), removing the
boilerplate that motivated the deferral.

The current upload path stores files untouched, so phone photos keep their
EXIF rotation, every avatar is re-downloaded full-size by clients, and no
thumbnail or responsive variant can be served.

## Decision

Un-defer image conversion and variants using only framework capabilities;
signed private URLs and events remain deferred.

### Write path (upload normalization)

- `UploadMediaAction` runs decodable uploads through
  `Image::fromUpload($file)->orient()->optimize()`: EXIF orientation first,
  then WebP re-encode at the pipeline default quality.
- The persisted row always describes the processed file (`mime_type`,
  `size`) and `meta` gains `width`/`height`.
- Non-processable MIME types are stored untouched; undecodable bytes that
  passed extension validation fall back to raw storage instead of failing
  the upload.
- No new configuration keys: defaults live inline in the action.

### Read path (variant endpoint)

- New authenticated route `GET /api/v1/media/{media}/variant`
  (`api.v1.media.variant`) with required `w` (integer, 32..2000) and
  optional `format` (`webp|jpg`).
- Authorization follows the existing policy (`view`: owner or
  `media.view` permission); Sanctum authentication replaces signed URLs as
  the abuse guard for this API-only kit.
- The controller returns an image response via
  `Image::fromStorage()->scale(width:)->toFormat()->quality(80)
  ->toResponse($request)` with cache headers: ETag derived from
  `updated_at|id|w|format`, `max-age=31536000`, public visibility, and a
  manual 304 short-circuit on matching `If-None-Match`.
- `scale()` never upscales, bounding work without a separate dimension cap.
- Derived-file caching (writing thumbnails to disk on first request) is
  deliberately postponed; on-the-fly generation with long-lived cache
  headers keeps the implementation small until traffic demands otherwise.
- Non-image media yields a `422` problem with `validation.media_not_image`.

## Consequences

- No new composer dependencies; GD ships with PHP and Intervention Image
  v4 was already present.
- Existing uploads created before this change keep their original bytes
  and remain servable; only new uploads are normalized.
- Variant generation costs CPU per cold request; the year-long max-age and
  ETag reuse absorb repeat traffic. Revisit derived-file caching if storage
  CPU becomes measurable.
- Signed private URLs stay deferred: private media remains reachable only
  through authenticated endpoints.
