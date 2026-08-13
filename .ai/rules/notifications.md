---
paths:
  - 'modules/*/Notifications/**'
  - 'app/Notifications/**'
---

# Notifications

## Goal

Notifications in `app/Notifications/` (global) or `modules/{Module}/Notifications/` (module-specific).

## Rules

1. Queue-able, via `ShouldQueue`
2. Descriptive naming (VerifyEmail, ResetPassword)

## Forbidden

- No notifications called directly in controllers (via action/service)
