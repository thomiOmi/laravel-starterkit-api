# ADR-0041: Upload Original Writes Resolve Through the Path Generator

- Status: Accepted
- Date: 2026-10-13

## Context

ADR-0040 wired every derivative read and write through `MediaPathGenerator` but deliberately left original-file writes on `MediaPrefix`: `UploadMediaAction::storeRaw()` and `storeProcessedImage()` stored uploads under the default layout before the row existed, while `getPath()` reads honored `custom_path_generators`. For custom generators the seam was therefore half-built — derivatives were fully consistent, but an uploaded original landed on the default layout while reads looked it up under the custom one, so eager generation found no source file and every custom upload was silently broken.

## Decision

`UploadMediaAction` now resolves original storage paths through the same generator that later reads them back, via an unsaved `Media` carrier (never persisted) in a private `originalUploadPath()` seam: `storeRaw()` stores at the probed path, `storeProcessedImage()` stores into its directory, and single-file replacement deletes the replaced original through a probe built from the pre-save snapshot. The default layout is byte-identical, so existing uploads, cleanup, and deletion behavior do not change.

This relies on one generator contract expectation, now documented on the seam: the directory part of `getPath()` must not depend on the file name, because the processed-image flow only knows a candidate name when it picks the directory. The default generator and both in-repo test doubles satisfy this.

## Consequences

- Custom generators are consistent end to end: originals, eager conversions, derived cache, responsive siblings, cleanup, and deletion all resolve through one authority. Proven by HTTP upload and single-file replacement tests under a custom generator.
- Existing custom generators automatically relocate original uploads on their next write; previously their `getPath()` override only affected reads. That behavior change is the stated purpose of the seam, not a regression.
- One more seam consumer to keep in mind when the generator contract next changes: upload writes join the seven derivative sites as the eighth reader/writer of generator paths.
