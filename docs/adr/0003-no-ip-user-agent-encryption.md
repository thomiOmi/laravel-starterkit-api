# ADR-0003: No IP / User-Agent Encryption in personal_access_tokens

- Status: Accepted
- Date: 2026-08-03

## Context

A common hardening suggestion is to encrypt the IP address and user-agent columns in `personal_access_tokens` so that identifying data is not stored in plaintext.

## Decision

Do not encrypt IP or user-agent in `personal_access_tokens`.

## Consequences

- Avoids false security: the server already logs IPs, so encryption does not meaningfully raise the privacy bar.
- Breaks diagnostics and debugging (tokens cannot be correlated to devices without decryption keys).
- Keeps the device-token UX (token naming + per-device revocation) simple.
