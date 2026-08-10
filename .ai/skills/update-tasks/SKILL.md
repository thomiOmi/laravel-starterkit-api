---
name: update-tasks
description: >
  Maintain TASKS.md, the project's operational tracker and agent scratchpad.
  Use whenever work starts or finishes, a plan is made, status needs recording
  ("catat progress", "update TASKS", "apa yang dikerjakan", "snapshot status"),
  a backlog/known issue item is opened or closed, or a session hands off to a
  future one. Also use when deciding where information belongs: decisions go to
  ADRs (create-adr), product scope to PRDs (create-prd), conventions to
  .ai/rules — TASKS.md only tracks execution.
metadata:
  version: "1.0"
---

# Update TASKS.md

## What TASKS.md is

TASKS.md is a gitignored operational tracker at the repo root. It is a scratchpad for the agent and the developer: what was done, what is next, what is deferred. It is NOT a source of truth — decisions belong in `docs/adr/`, product scope in `docs/prd/`, and settled conventions in `.ai/rules/` (recorded via `record-rule`). Because it is gitignored, anything that must survive commits and clones goes into `docs/` first.

## Structure

Keep the sections in this order and update them as they change:

- **Header**: one line stating the file is the operational tracker, not the decision source of truth.
- **Status snapshot** (`## Status snapshot (YYYY-MM-DD)`): date, branch + HEAD short SHA, one-line summary of project state, and dated bullets of notable changes. Refresh the date and HEAD on every update (`git rev-parse --short HEAD`).
- **Fokus aktif**: the current work, in priority order. Fill when starting work; empty it when work ends.
- **Backlog**: deferred items, each with a status word (DITUNDA / SKIP / DITUTUP) and a date.
- **Known issues**: open issues; closed ones stay listed with a DITUTUP date and reopening conditions.
- **Agent notes**: non-obvious pitfalls and working knowledge that would otherwise be lost between sessions (e.g. queue semantics, auth pitfalls). One or two lines each.
- **Definition of Done**: the project quality gate (lint, types, coverage, tests).
- **Pointer dokumen**: the `docs/` map so any session can navigate without re-discovery.

## Update rules

1. Update after every meaningful change: task started, task finished, snapshot refresh, issue opened/closed.
2. Snapshot line: always update the date and the HEAD SHA — a stale snapshot is worse than none.
3. Closed/deferred items: annotate with the date and a one-line reason, do not delete — they are the project memory.
4. Do not keep permanent per-feature `- [ ]` checklists in TASKS.md for implementation breakdowns — use the session's `todowrite` tool for that. If a deferred plan needs enough detail to resume later (like a skipped phase), write that detail inline where it is deferred.
5. Bilingual is fine: the file mixes Indonesian and English as established — keep new entries in whichever language the surrounding section uses.
6. When a decision or convention change surfaces during tracked work, route it: create the ADR (create-adr) or record the rule (`record-rule`), then remove the decision content from TASKS.md and note that it moved.
