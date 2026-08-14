---
name: create-adr
description: >
  Record an Architecture Decision Record (ADR) for a settled design decision
  in Nygard format. Use whenever the user makes or changes an architectural
  choice, asks "why is this code like this", "should we do X or Y", "deviation
  from a past decision", or a decision needs a written rationale before coding -
  also when reviewing existing ADRs or deciding whether a convention change
  needs one. If the request is about product requirements instead, use the
  create-prd skill; for tracking work use update-tasks.
metadata:
  version: "1.0"
---

# Create ADR

## Purpose

ADRs are the canonical answer to "why is the code like this?". They record the reason behind a settled decision, not just the outcome. Read the relevant ADR before changing a settled convention — if you disagree, write a new ADR that supersedes it rather than silently deviating.

## When to create an ADR

Create one when a decision is settled and any of these apply:

- It changes a convention other modules or developers rely on (routing, response format, auth, middleware, module structure).
- It rejects or supersedes an existing ADR.
- It has a non-obvious rationale someone would otherwise relitigate or accidentally violate.

Do not create ADRs for: bug fixes, implementation details, or anything inferable from the code.

## Process

1. **Check for existing decisions first.** Read `docs/adr/README.md` (index) and grep `docs/adr/` for the topic. If a decision already exists, do not duplicate it — extend or supersede it.
2. **Read `docs/adr/template.md`** and one recent ADR (e.g. the last few in the index) to match style and depth.
3. **Number the file** as the next sequential number in the index (never reuse or reorder; a Superseded decision keeps its number and points to the replacement). Filename: `NNNN-<kebab-slug>.md` (e.g. `0023-idempotency-strategy.md`).
4. **Write the ADR** following the template:
   - Status: `Proposed`, `Accepted`, `Deprecated`, or `Superseded by ADR-NNNN`.
   - Date: `YYYY-MM-DD`.
   - Context: the problem and environment motivating the decision — include rejected alternatives and why they lost.
   - Decision: the change, in one or a few sentences.
   - Consequences: what becomes easier and what becomes harder.
   - When superseding: record the deviation explicitly in Context/Consequences and reference the old ADR by number.
5. **Write in English.** One file per decision. Keep it focused — several paragraphs, not a book.
6. **Update the index**: add a row to the table in `docs/adr/README.md` (ID, short Decision, Status, Date).
7. **Link from the PRD**: add the ADR number to the Related ADRs field of every PRD it affects (`docs/prd/*.md`).
8. **Keep trackers consistent**: if the decision came out of TASKS.md work, move it out of the tracker via the `update-tasks` skill — the ADR is now the source of truth.

## Do not

- Do not edit or renumber existing ADRs retroactively; record new decisions as new ADRs.
- Do not record transient state (what is in progress, who decided) — that belongs in TASKS.md.
