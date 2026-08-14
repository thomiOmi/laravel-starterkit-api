---
paths:
  - 'app/Concerns/**'
---

# Concerns

## Shared cross-module behavior lives in app/Concerns
Traits in `app/Concerns` are the shared vocabulary for behavior reused across modules (shared concerns in `app/`, module-scoped concerns inside the module). Current inventory: `FormatDate` (date formatting, used by Resources), `HasDefaultBehavior` (ULID + soft deletes + serializeDate, used by Models), `PasswordValidationRules` and `ProfileValidationRules` (user/password validation helpers, used by Requests). Check the inventory before adding a new concern; a trait used by a single module belongs in that module.

## FormatDate returns null for empty/unparseable strings; formatDateTime is the single format source
formatDate(DateTimeInterface|string|null) returns null for empty strings and unparseable strings (graceful, no exception) and null input. The 'Y-m-d H:i:s' format lives only in formatDateTime(); HasDefaultBehavior::serializeDate delegates to it. Never setTimezone/shift the instant when formatting - the object's own timezone is preserved.
