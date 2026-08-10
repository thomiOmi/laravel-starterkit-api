# ADR-0021: Social Auth Design (Pivot, Stateless State, Email Binding)

- Status: Accepted
- Date: 2026-08-09

## Context

Phase 3 added Google/GitHub login via Socialite. Design questions: where to store provider bindings, how to keep OAuth callbacks session-free (API-only kit), and how to bind emails.

## Decision

- **Pivot table** `social_accounts` (not columns on `users`): one user can link multiple providers; unique `(provider, provider_id)` + `(user_id, provider)`; ULID PK; `user_id` FK cascadeOnDelete. Single migration: create pivot, copy legacy data, drop old columns.
- **Stateless OAuth state**: `SocialState` (Crypt-encrypted `{action: login|link, user_id?, exp}`, TTL 10 minutes) in `Modules\IAM\Support`; the callback does not use sessions.
- **Email binding**: bind only if an existing user has the same email; if the existing user is unverified, bind and verify immediately (provider proves ownership; creating a new user would crash on unique email). Empty provider email becomes synthetic `{provider}-{id}@social.local`.
- **Unlink guard lockout**: 422 if `password === null && socialAccounts()->count() <= 1`.
- **Avatar 2-step** (Stripe pattern): upload `POST v1.media` (public disk) then `PUT me` with `avatar: media_id`; `UpdateProfileAction` validates public disk + ownership.
- **Email change**: nulls `email_verified_at`, sends VerifyEmail notification, verified-only routes return 403 until the signed link is verified.

## Consequences

- One provider binding per (user, provider) pair; multi-provider accounts supported.
- Callbacks work without cookies/sessions — required for an API-only kit.
- Email rebinding is safe: no duplicate accounts on same email, no account takeover via unverified binding.
- Avatar flow reuses the Media module (ADR-0015).
