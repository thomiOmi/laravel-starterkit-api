---
paths:
  - 'modules/*/Actions/**'
---

# Actions

## Final readonly actions with handle()
Actions are single-responsibility business logic classes: final readonly, with a single public handle() method and explicit typed parameters; no HTTP Request dependency (controllers pass extracted data). Return values flow directly to the controller response or the caller. Each Action gets a unit test in modules/*/Tests/Unit (Pest, expect()->toThrow for exceptions, specific expectations over toBeTrue()/toBeFalse() wrappers).
