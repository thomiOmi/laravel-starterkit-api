---
paths:
  - '**'
---

# General

## Ship all work through pull requests
All changes to `main` go through a pull request (rebase merge) - direct pushes are not allowed and bypass branch protection. Every task: create a branch (feat/..., fix/..., docs/...), commit locally, push the branch, open a PR via `gh pr create`, wait for the required status checks, then merge with rebase. Never `git push origin main` even for solo work.
