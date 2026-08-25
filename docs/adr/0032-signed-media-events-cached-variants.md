# ADR-0032: Signed Media URLs, Media Events, and Cached Variants

- Status: Accepted
- Date: 2026-08-26

## Context

ADR-0030 deferred signed private URLs and events; ADR-0031 additionally
postponed derived-file caching for image variants. Private media remained
practically unreachable from clients (`MediaResource` resolves its url to
null), uploads and deletions were invisible to listeners, and every variant
request paid the full image-pipeline cost.

## Decision

Un-defer all three capabilities using framework primitives only.

### Signed private URLs

- `App\Contracts\MediaUrlResolver` gains a third method,
  `signed(string $mediaId, int $ttlMinutes): string`, implemented with
  `Uri::temporarySignedRoute`.
- New public route `GET /api/v1/media/{media}/file`
  (`api.v1.media.file`) guarded by the native `signed` middleware plus
  `throttle:api`; it streams the stored file via `Storage::response()`.
- The signature is the credential: whoever holds the link may stream the
  file until expiry, regardless of visibility. Callers mint URLs only for
  identities already authorized to see the media.
- `GET /api/v1/media/{media}?expires=<minutes>` (validated 1..1440)
  swaps the resource url for such a signed link; without the parameter
  behaviour is unchanged (private media keeps resolving to null).

### Media events

- `Modules\Media\Events\MediaUploaded` and `MediaDeleted` carry the model
  and are dispatched by their respective actions after persistence.
- Both are `final readonly` classes using `Dispatchable` +
  `SerializesModels` only; `InteractsWithSockets` cannot be used on
  readonly classes (non-readonly trait property).
- The kit ships no listeners; applications attach their own.

### Derived-file caching for variants

- The variant controller now checks
  `variants/{id}/{version}-{width}-{format}.{ext}` before generating;
  on miss it generates, writes with `storeAs`, then streams. The
  `updated_at` timestamp in the path invalidates old files naturally when
  media changes.
- `DeleteMediaAction` removes the media's whole `variants/{id}`
  directory together with the stored file.
- No configuration keys; naming and lifetime stay inline.

## Consequences

- Route names in the Media module dropped their duplicated segment:
  groups no longer add `name('media.')` on top of the RouteServiceProvider
  alias, so final names match the documented `api.v1.{module}.{name}`
  contract (`api.v1.media.upload`, `.index`, `.show`, `.variant`,
  `.delete`, `.file`). This corrects a long-standing deviation.
- Variant storage grows with requested sizes/formats; cleanup happens at
  delete time. Sweepable orphans remain possible on crash, consistent with
  the existing file-first delete order.
- Signed links bypass per-request authorization by design; short TTLs
  (default guidance 15 minutes) bound exposure.
