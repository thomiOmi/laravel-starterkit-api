---
paths:
  - app/Concerns/FormatDate.php
---

# Concerns

## FormatDate returns null for empty/unparseable strings; formatDateTime is the single format source
formatDate(DateTimeInterface|string|null) returns null for empty strings and unparseable strings (graceful, no exception) and null input. The 'Y-m-d H:i:s' format lives only in formatDateTime(); HasDefaultBehavior::serializeDate delegates to it. Never setTimezone/shift the instant when formatting - the object's own timezone is preserved.
