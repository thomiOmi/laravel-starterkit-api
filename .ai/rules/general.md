---
paths:
  - '**'
---

# General

## Ship all work through pull requests
All changes to `main` go through a pull request; direct pushes are not allowed. Flow: create a branch (feat/..., fix/..., docs/...), commit, push, open the PR with `gh pr create --body-file <file>`, and wait for the required checks (Laravel (PHP 8.4), Laravel (PHP 8.5)). Pull requests are merged with SQUASH only, by the owner (or by an agent only when the owner explicitly asks for that specific PR); the squashed commit title ends with `(#nnn)`. Never `git push origin main`, even for solo work.
