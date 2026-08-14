---
name: create-prd
description: >
  Create a Product Requirements Document (PRD) following the project's
  documentation conventions. Use whenever the user asks for a PRD, feature
  requirements, a spec for a new feature, "write a PRD", "write requirements",
  "feature specification", or wants to capture product scope before coding -
  even when they just describe an idea or say "write this feature up". If the
  request is about an architecture or design decision instead, use the
  create-adr skill; if it is about tracking work, use update-tasks.
metadata:
  version: "1.0"
---

# Create PRD

## Purpose

The PRD captures WHAT the product needs and WHY, before any code is written. It is not a design document — decisions about HOW belong in ADRs. Following this skill keeps new PRDs consistent with the existing `docs/prd/` structure.

## Process

1. **Read conventions first.** Read `docs/prd/template.md` and `docs/prd/README.md`. Skim the main PRD `docs/prd/starterkit-api.md` (or the relevant feature PRD) to match tone, terminology, and requirement ID conventions.
2. **Clarify before writing.** Ask only 3-5 essential questions when the prompt is ambiguous: problem/goal, target user, scope boundaries, success criteria, priority. Number the questions (1, 2, 3) and offer A/B/C/D options so the user can answer with selections like "1A, 2C". Skip questions whose answers are inferable from the prompt.
3. **Draft the PRD** from `docs/prd/template.md`, filling every section:
   - Metadata: Status `Draft`, Version 0.1.0, Date `YYYY-MM-DD`, Related ADRs (empty list unless a decision already exists).
   - Requirement IDs use the feature-prefix pattern from the main PRD: `{FEAT}-01`, `{FEAT}-02` (e.g. `AUTH-01`, `IAM-01`). Number sequentially.
   - Functional Requirements as a table with MoSCoW priority (Must/Should/Could).
   - Acceptance Criteria as observable, testable statements — they become the test contract.
   - Keep requirements explicit enough for a developer who knows nothing about the conversation.
4. **Write in English** — all documents in `docs/` are English.
5. **Confirm the draft** with the user before finalizing; incorporate feedback and re-draft.

## Output

- Save as `docs/prd/{feature-slug}.md` (e.g. `docs/prd/social-auth.md`). Only create a dedicated PRD file when the feature has product-level scope beyond a single module — otherwise a section in the main PRD suffices.
- Add the document to the index table in `docs/prd/README.md`.
- If the feature belongs in the main PRD: add the requirements there and link the new PRD.
- Record the pending work in `TASKS.md` (Active focus) via the `update-tasks` skill so the next session knows it is queued.

## Do not

- Do not implement the feature after writing the PRD.
- Do not write design decisions inside the PRD — route them to the `create-adr` skill.
- Do not invent requirement IDs that collide with existing ones — check the main PRD first.
