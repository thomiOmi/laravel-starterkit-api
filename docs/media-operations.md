# Media Operations Runbook

Operational guide for monitoring and maintaining the Media module in staging and production. Covers structured logs, queue retry behavior, scheduled cleanup, and the common failure paths.

## Structured logs

All Media lifecycle logs use structured context keys so they can be queried by log aggregation. The default channel is the application log channel (`LOG_CHANNEL`).

| Level | Message | Context keys | Source |
|-------|---------|--------------|--------|
| info | `Media processing completed.` | `media_id`, `duration_ms` | `ProcessMediaJob` |
| info | `Media processing skipped: media not found.` | `media_id` | `ProcessMediaJob` |
| error | `Media processing failed.` | `media_id`, `duration_ms`, `error` | `ProcessMediaJob` |
| warning | `Media conversion failed.` | `media_id`, `conversion`, `error` | `MediaConversionService` |
| warning | `Media conversions failed.` | `media_id`, `error` | `UploadMediaAction` (sync path) |
| warning | `Media responsive images failed.` | `media_id`, `error` | `UploadMediaAction` (sync path) |
| warning | `Media orphan files detected.` | `count`, `destructive`, `files` | `media:cleanup` |
| warning | `Media records with missing files.` | `count`, `media_ids` | `media:cleanup` |
| warning | `Media download failed.` | `url`, `error` or `status` | `DefaultDownloader` |

Useful filters:

- Failed processing: message `Media processing failed.` or level `error` with `media_id`.
- Slow processing: sort by `duration_ms` on `Media processing completed.`
- Single media investigation: context `media_id` across all levels.

Processing duration (`duration_ms`) measures wall-clock time for one job attempt, from lookup through conversion and responsive generation.

## Processing state machine

`media.processing_status` transitions:

```text
pending -> processing -> processed
                     \-> failed
```

- `pending`: job dispatched (queue mode) or about to process synchronously.
- `processing`: job started, source file verified.
- `processed`: conversions and responsive images finished; `processed_at` set.
- `failed`: exception recorded in `processing_error`; `processed_at` remains null.

Inspect a stuck item:

```bash
php artisan tinker --execute 'echo \Modules\Media\Models\Media::query()->where("processing_status", "failed")->pluck("id")->implode("\n");'
```

Reprocess after fixing the root cause:

```bash
php artisan media:reprocess --id=01H...           # sync
php artisan media:reprocess --collection=avatars --queued
php artisan media:reprocess --conversion=thumbnail
```

## Queue retry policy

`ProcessMediaJob` carries an explicit retry policy:

| Property | Value | Rationale |
|----------|-------|-----------|
| `$tries` | `3` | Transient storage/image failures recover; permanent failures fail fast. |
| `$timeout` | `60` | Hard ceiling per attempt; must stay below queue `retry_after` (90s default in `config/queue.php`). |
| `$backoff` | `[10, 30]` | 10s before attempt 2, 30s before attempt 3. |

Notes:

- Keep `$timeout` strictly less than the connection `retry_after`, otherwise the connection re-delivers the job while it is still running (double execution).
- After `$tries` is exhausted the job is written to `failed_jobs` (`QUEUE_FAILED_DRIVER`, default `database-uuids`) and the media row stays `failed`.
- Queue mode is enabled with `MEDIA_QUEUE=true` (`media.queue`); otherwise conversion runs inline during upload.

Worker:

```bash
php artisan queue:work --queue=default
# or the project's dev stack
composer dev
```

Inspect failures:

```bash
php artisan queue:failed
php artisan queue:retry all   # or a specific UUID
```

## Scheduled cleanup

`routes/console.php` schedules a daily dry-run:

```text
media:cleanup  daily at 03:00  (withoutOverlapping)
```

Default behavior is report-only: orphan files under known media paths are listed, nothing is deleted. `conversions/derived/` is excluded because derived variants are regenerable cache.

Manual runs:

```bash
php artisan media:cleanup --dry-run   # same as scheduled default
php artisan media:cleanup             # dry-run (no --force)
php artisan media:cleanup --force     # actually delete listed orphans
```

Promote the scheduled job to destructive deletion only after reviewing the report (for example, after a successful deployment that intentionally removed media). Prefer an explicit one-off `--force` run over changing the schedule.

The command also reports DB rows whose source file is missing (`Media records with missing files.`). Restore the file from backup or delete the row; do not leave missing sources unattended.

Verify the schedule:

```bash
php artisan schedule:list
```

## Health checks

| Check | Command / signal | Healthy |
|-------|------------------|---------|
| Schema | `php artisan migrate:status` | All migrations run |
| Queue backlog | `php artisan queue:monitor` or jobs table depth | No sustained growth |
| Failed jobs | `php artisan queue:failed` | Empty or triaged |
| Failed media | `processing_status = failed` count | Empty or triaged |
| Orphans | `php artisan media:cleanup --dry-run` | `No orphan files found.` |
| Scheduler | `php artisan schedule:list` | `media:cleanup` present, daily 03:00 |
| Storage link | `php artisan storage:link` | Public media URLs resolve |

## Incident playbook

### Uploads return 201 but media never becomes processed

1. Confirm queue mode: `config('media.queue')` / `MEDIA_QUEUE`.
2. Check worker is running for the configured queue name.
3. Search logs for `Media processing failed.` with the `media_id`.
4. Check `media.processing_error` for the stored message.
5. Fix root cause (disk credentials, missing source, image driver), then `media:reprocess`.

### Spike in `Media conversion failed.` warnings

1. Filter by `conversion` name to see if one definition is bad.
2. Check available disk/memory on the worker (GD/Imagick decode limits).
3. Compare with `media.image.*` limits and the configured conversion dimensions.
4. Reprocess the affected collection after correcting the definition.

### Orphan report grows every day

1. Run `media:cleanup --dry-run` and review `files`.
2. Causes: interrupted uploads, single-file replacement races, manual disk edits.
3. If confirmed safe, run `media:cleanup --force` once.
4. If orphans keep reappearing, trace the upload path before deleting again.

### Jobs stuck in `processing`

1. `timeout` (60s) should release the job; if the worker died hard, `retry_after` (90s) re-delivers.
2. Check worker logs and `failed_jobs`.
3. Force status back through reprocess after clearing the stuck worker.

## Related docs

- Module README: `modules/Media/README.md`
- ADRs: `docs/adr/0030-custom-media-module.md`, `docs/adr/0032-signed-media-events-cached-variants.md`, `docs/adr/0036-media-polymorphic-squash.md`
- Architecture: `docs/architecture.md`
