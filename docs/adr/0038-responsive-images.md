# ADR-0038: Responsive Images Generation Design

- Status: Accepted
- Date: 2026-09-04

## Context

The `media.responsive_images` JSON column exists (migration `2026_08_23_024502`, cast in `Media`), plus contracts with zero generation behind them: `MediaPathGenerator::getPathForResponsiveImages()`, `MediaFileNamer::responsiveFileName()`. `UploadMediaAction` persists `'responsive_images' => []` on every upload. Spatie generates responsive variants via a width calculator (`FileSizeOptimizedWidthCalculator`: widths each ~30% smaller), a `GenerateResponsiveImagesJob`, tiny blurred placeholders, and `responsive_images` JSON consumed as `srcset`. This is an API-only project: no Blade components consume placeholders or JS, API clients build `srcset` themselves from URLs.

## Decision (proposed: Option B minimal)

- **Option A — Spatie-full:** width calculator interface + job + tiny blurred placeholder + JS snippet + `getSrcset()` helper. Rejected: placeholders/JS are web-view concerns with no consumer in an API-only kit; widens scope to image-analysis tuning.
- **Option B — Minimal (proposed):** `responsive.widths` config (default `[320, 640, 1024, 1600]`, capped by original width, never upscale) + `GenerateResponsiveImagesAction` reusing `MediaConversionService::generateOne()` per width into `responsive-images/` dir (paths via existing `getPathForResponsiveImages()` + `responsiveFileName()`) + fill `responsive_images` JSON `{width: {url, size}}` + `Media::getSrcset(?string $conversion): string` helper returning `url 320w, ...` + `MediaResource` exposes `responsive` map. Trigger: opt-in per collection via `MediaCollection::withResponsiveImages()` (new flag; default off, zero behavior change) + existing `media.queue` decides sync vs `ProcessMediaJob`.
- `withResponsiveImages()` stubs that exist today stay no-ops until this ADR is accepted and implemented.

## Consequences

- API clients get ready `srcset` data without new dependencies (GD pipeline already present); no placeholder/JS surface to maintain.
- Widths config keeps per-project tuning without code changes; capping avoids upscaling artifacts.
- Implemented as designed, with one deviation: the JSON stores `{width: {path, size}}` instead of `{width: {url, size}}` — URLs are built on read via `Media::getSrcset()` so disk/URL changes do not stale stored rows.

## Implementation (PR feat/media-responsive)

- `GenerateResponsiveImagesAction` (+ static `wantsResponsive()` gate shared by upload and job paths), `MediaCollection::withResponsiveImages()` opt-in flag, `media.responsive.widths` config, `Media::getSrcset()`, `srcset` resource field, `ProcessMediaJob` runs responsive after conversions, `UploadMediaAction` dispatches job-or-inline honoring `media.queue`.
