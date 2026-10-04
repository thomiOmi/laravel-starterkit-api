# ADR-0039: MediaPathGenerator Owns Only the Original Path, MediaPrefix Owns Every Path

- Status: Accepted
- Date: 2026-10-05

## Context

ADR-0037 and ADR-0038 were written while the media module was still being shaped, and both state that derivative paths are produced by the path generator: ADR-0037:13 says `getPathForConversions` builds conversion paths via `dirname`, and ADR-0038:13 says responsive paths come from `getPathForResponsiveImages()`. ADR-0038:8 also flagged both methods as "contracts with zero generation behind them", so the gap was visible while those ADRs were being written.

In the end the calls never landed that way. Commit `dba6496` introduced `Modules\Media\Support\MediaPrefix` and routed every stored path through it. Production now builds derivative paths inline: `MediaConversionService:200` uses `MediaPrefix::join('conversions', ...)` for eager conversions, `MediaConversion:213` uses `MediaPrefix::join('conversions/derived', ...)` for the on-demand cache, and `GenerateResponsiveImagesAction:136` uses `dirname($path).'/responsive-images'`. `MediaPrefix` appeared in no ADR at all.

That left `getPathForConversions()` and `getPathForResponsiveImages()` with zero production call sites, which reads as dead code. A dead-code audit reported them as violating ADR-0017. That conclusion was wrong, but only because the decision record contradicted the code and nobody had reconciled them.

Checked against Spatie Media Library, which inspired this module's design (ADR-0030), the root cause is a shape mismatch rather than a bad idea. Spatie's `PathGenerator` has three methods that all return **directories** and share one `getBasePath($media)` keyed by media id; `getPathForConversions(Media $media)` takes no `$conversion` argument. Filenames are owned separately by `FileNamer`. Our two extra methods instead return **file paths**, and `getPathForConversions` gained a `$conversion` parameter. Callers need a directory for `storeAs()` and `deleteDirectory()`, so the methods could not be called as written.

The customization intent is already half-served: `MediaFileNamer` owns original, conversion, and responsive names and all three methods have production callers. What is not customizable today is the **directory** layout of derivatives.

Alternatives rejected:

- **Delete the two methods.** Rejected because the seam is a deliberate Spatie-inspired extension point and ADR-0017 targets speculative *branches*, not an interface designed for a stated purpose.
- **Reshape them now and wire up the seven call sites.** Deferred, not rejected. It is a pure refactor with no stored-path change and no migration. The three write sites are `MediaConversionService:200`, `MediaConversion:213`, and `GenerateResponsiveImagesAction:136`; the four read/delete sites are `MediaCleanupCommand:44,133`, `UploadMediaAction:711-712`, and `DefaultFileRemover:28-29`. All seven must move together, or `media:cleanup` will delete the wrong files. Doing it well is its own task.
- **Adopt Spatie's `conversions/{name}/{basename}` layout.** Rejected for now: it is layout-faithful but changes where every future derivative is stored, which requires a data migration to move existing files and rewrite `media_conversions.path` rows.

## Decision

`MediaPathGenerator` owns the path of the **original** file only. `MediaPrefix` is the single authority for the optional `media.prefix` and for the layout of derivative directories, and `MediaFileNamer` owns every filename. Document the actual division in `modules/Media/README.md` so a swapped `path_generator` is not expected to change derivative locations.

`getPathForConversions()` and `getPathForResponsiveImages()` are **not** dead code and ADR-0017 does not apply to them. They stay as the intended seam, unwired, until their signatures return directories. Changing them later is a deliberate task, not cleanup.

This supersedes the path-generation parts of ADR-0037 and ADR-0038. Their other decisions (ULID `collection_name`/`file_name` structure, responsive image generation and opt-in `withResponsiveImages()`) are unaffected and remain in force.

## Consequences

- Reads and writes agree on one path authority, and `media.prefix` keeps working through a single place.
- Swapping `media.file_namer` or `media.path_generator` has predictable, documented effects: names change, or the original file path changes.
- Customizing derivative **directories** is still not supported. That remains a real limitation and is now stated rather than implied.
- ADR-0037 and ADR-0038 no longer describe the current implementation for path generation; this ADR is the source of truth for it.
- Anyone who wants the seam wired must first reshape the two methods to return directories, then move the seven call sites together. Until then the methods being unused is a known, intentional state, not an oversight to clean up.