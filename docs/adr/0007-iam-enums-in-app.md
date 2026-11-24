# ADR-0007: Enum Placement (Shared Vocabulary vs Module-Owned)

- Status: Accepted (revised 2026-08-14)
- Date: 2026-08-07

## Context

`UserStatusEnum` is used by root migrations; `PermissionEnum` is used by `app/Http/Requests/BulkActionRequest.php`. Both sit outside the architecture-test allowlist, so moving them into the IAM module would break `modules should be isolated`.

## Decision (original)

IAM enums remain in `app/Enums`. The rule is: files outside the allowlist must not import module code.

## Consequences (original)

- One principle covers all cases: root files never import from modules.
- Enum usage is not a module-inconsistency; it is the expected direction of dependency.
- New feature modules should keep module-private enums inside the module; only shared/root-referenced enums belong in `app/Enums`.

## Revision (2026-08-14): Explicit Placement Criteria

The original ADR left "shared" implicit. The criteria are now explicit and verified against every enum in `app/Enums`:

- An enum belongs in `app/Enums` (shared vocabulary, ADR-0005) when it is consumed by root-level files (e.g. `database/migrations`, `app/Http/**`, `app/Providers/**`) or by 2+ modules.
- An enum used only inside one module belongs in `modules/{Module}/Enums` and is moved there as part of module consistency work (P5).

Audit result (usage as of 2026-08-14):

| Enum | Consumers | Placement |
|---|---|---|
| `PermissionEnum` | app `BulkActionRequest`, IAM routes/requests/policies, Media seeder | `app/Enums` (shared) |
| `RoleEnum` | app `AppServiceProvider`, IAM policies, Media seeder | `app/Enums` (shared) |
| `UserStatusEnum` | root migration `create_users_table`, IAM tests | `app/Enums` (root-referenced) |
| `MediaVisibilityEnum` | Media only (Resource, Request, Payload, Action, tests) | `modules/Media/Enums` (module-owned, move in P5) |

## Consequences (revision)

- Clear testable rule: grep usage; root or cross-module consumers mean `app/Enums`, single-module consumers mean the module.
- `MediaVisibilityEnum` moves to `modules/Media/Enums` with the module-consistency phase; its consumers update imports in the same change.
- Enum labels stay in `lang/{locale}/enums.php` keyed by basename regardless of placement, so moving an enum does not touch translation files.

## Corrections

- 2026-11-24: The module enums are already in their owning module, so the P5 move described above is obsolete: `MediaVisibilityEnum` lives at `modules/Media/app/Enums/MediaVisibilityEnum.php:7` and `MediaProcessingStatus` at `modules/Media/app/Enums/MediaProcessingStatus.php:7`; the "moved there as part of module consistency work (P5)" clause in the revision criteria, the `modules/Media/Enums` placement and "move in P5" in the audit table, and the "moves ... with the module-consistency phase" consequence no longer describe the code. The correct module path includes `app/`: `modules/{Module}/app/Enums/`. The audit table's consumer rows also need correction: `PermissionEnum` is used by Media production code in `modules/Media/app/Policies/MediaPolicy.php:8,41,57` and `modules/Media/routes/V1.php:5,24`, not by a Media seeder - `MediaSeeder::run()` is empty (`modules/Media/database/seeders/MediaSeeder.php:11-17`); `RoleEnum` has no Media seeder consumer (only `modules/Media/tests/Feature/MediaPolicyTest.php:5,74`), while root wiring uses it in `app/Providers/AppServiceProvider.php:8,179` and `app/Features/BetaFeature.php:8,17`; `UserStatusEnum` is root-referenced by `database/migrations/0001_01_01_000000_create_users_table.php:5,21` and also used across IAM application files, not only IAM tests; `MediaVisibilityEnum` consumers are Media-only (`modules/Media/app/Actions/UploadMediaAction.php:16`, `modules/Media/app/Models/Media.php:27`, `modules/Media/database/factories/MediaFactory.php:10`, plus tests). All five enums still satisfy the placement criteria; no code moved. (Original text above is kept as written.)
