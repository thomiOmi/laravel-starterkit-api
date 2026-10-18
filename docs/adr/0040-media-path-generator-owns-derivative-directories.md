# ADR-0040: MediaPathGenerator Owns Derivative Directories

- Status: Accepted
- Date: 2026-10-13

## Context

ADR-0039 left `getPathForConversions()` and `getPathForResponsiveImages()` as an intentional but unwired seam: both returned file paths, had zero production call sites, and production built derivative directories inline through `MediaPrefix`. A custom `path_generator` therefore moved original-file reads while every derivative read and write stayed on the default layout, so reads and deletes could disagree about where files live.

Reshaping the seam was deferred — deliberately, not rejected — until all seven call sites could move together: three writers (`MediaConversionService:200`, `MediaConversion:213`, `GenerateResponsiveImagesAction:136`) and four readers/deleters (`MediaCleanupCommand:44,133`, `UploadMediaAction:711-712`, `DefaultFileRemover:28-29`).

Alternatives rejected:

- **Keep the seam unwired.** Rejected: the file-path signatures cannot be called by code that needs directories for `storeAs()` and `deleteDirectory()`, so the seam would stay dead and custom generators inconsistent.
- **Keep `getPathForConversions(Media, $conversion)` and return a directory per conversion.** Rejected: the stored layout has no per-conversion subdirectories (eager files sit flat under `conversions/{id}`), so the parameter would be meaningless.
- **Adopt Spatie's `conversions/{name}/{basename}` layout.** Rejected (as in ADR-0039): it changes every stored derivative path and needs a data migration.
- **Re-apply the prefix when joining directory and filename.** Rejected during implementation: `MediaPrefix::join()` prepends `media.prefix` on every call, so joining an already-prefixed directory double-applies it (`tenant-a/tenant-a/...`, caught by `MediaStoragePrefixTest`). Callers concatenate onto generator directories instead, matching the existing responsive-images pattern.

## Decision

`MediaPathGenerator` now owns derivative **directories**: `getPathForConversions()` (the `$conversion` parameter is dropped) returns the eager conversions directory, new `getPathForDerivedConversions()` returns the on-demand derived-cache directory, and `getPathForResponsiveImages()` returns the responsive directory. `Media` exposes the three directories through `getConversionsDirectory()`, `getDerivedConversionsDirectory()`, and `getResponsiveImagesDirectory()`, all honoring `media.custom_path_generators`. All seven call sites build paths through these seams, and `media:cleanup` scans and excludes per-media directories so custom layouts are cleaned correctly.

The default layout is byte-identical (verified by the unchanged `MediaStoragePrefixTest` and `MediaCleanupCommandTest` suites): no stored path changes, no migration.

## Consequences

- Swapping `media.path_generator` now moves originals and derivatives together, and per-model `custom_path_generators` move derivatives end to end (proven by `MediaGeneratorsConfigTest`, `MediaDeleteTest`, and `MediaCleanupCommandTest` custom-generator cases).
- Reads and deletes structurally agree with writes, because every site resolves directories through the same media seams.
- Existing custom generators break and must be updated: the two methods changed from file paths to directories (plus one new method), and an old file-path return would corrupt every derived path. Both in-repo test doubles were migrated.
- Upload original writes still bypass the generator (`UploadMediaAction` stores via `MediaPrefix` before the row exists), so `custom_path_generators` remains read-only for original uploads. Wiring original writes is deferred future work, not part of this decision.
