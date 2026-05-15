# Testing Standards

We use **Pest PHP** for testing. Our strategy focuses on "outside-in" testing, driving tests through HTTP requests to verify the API's behavior.

## 1. Core Principles

- **Pest PHP**: Use the Pest testing framework for all new tests.
- **HTTP/Feature Tests**: Focus on testing endpoints rather than individual classes in isolation.
- **Outside-In**: Assert on the response status, JSON structure, and side effects (e.g., database records).
- **Happy & Unhappy Paths**: Every endpoint must have at least one test for a successful request and one for a failure (e.g., validation error).

## 2. Implementation Example

```php
<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

it('can store a new user', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->postJson('/api/v1/users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->assertStatus(Response::HTTP_CREATED)
        ->assertJsonPath('data.name', 'John Doe');

    $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
});

it('returns problem details when email is missing', function (): void {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->postJson('/api/v1/users', [
            'name' => 'John Doe',
        ])
        ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
        ->assertJsonPath('title', 'Validation Error')
        ->assertJsonStructure(['type', 'title', 'status', 'detail', 'errors']);
});
```

## 3. Directory Structure

Tests should mirror the structure of the code they test:

```text
modules/
  {Module}/
    Tests/
      Feature/
        V1/
          StoreTest.php
          IndexTest.php
```

## 4. Anti-Patterns

- ❌ Do not use PHPUnit for new tests (prefer Pest).
- ❌ Do not skip unhappy path testing.
- ❌ Do not assert on internal implementation details; focus on the public API behavior.
- ❌ Do not mock Eloquent models unless absolutely necessary; use `RefreshDatabase` and factories instead.
