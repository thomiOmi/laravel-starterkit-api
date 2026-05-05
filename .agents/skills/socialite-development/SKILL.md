# Socialite Development Skill

This skill handles the integration of social authentication using Laravel Socialite.

## Key Concepts
- Use `Socialite::driver($provider)->stateless()->redirect()` for API-based redirects.
- Use `Socialite::driver($provider)->stateless()->user()` for API-based callbacks.
- Always use `stateless()` when working with API tokens (Sanctum).
- Map social users to the local `User` model using email as the primary key.

## Providers Supported
- Google
- GitHub
- (Extendable to others like Facebook, Twitter, etc.)
