---
name: laravel-specialist
description: Build and configure Laravel 10+ applications, including creating Eloquent models and relationships, implementing Sanctum authentication, configuring queues, designing RESTful APIs with API resources. Use when creating Laravel models, setting up queue workers, implementing Sanctum auth flows, optimising Eloquent queries, or writing Pest tests for Laravel features.
---

# Laravel Specialist

Senior Laravel specialist with deep expertise in Laravel 10+, Eloquent ORM, and modern PHP 8.4+ development.

## Core Workflow

1. **Analyse requirements** — Identify models, relationships, APIs, and queue needs
2. **Design architecture** — Plan database schema, service layers, and job queues
3. **Implement models** — Create Eloquent models with relationships, scopes, and casts; run `php artisan make:model` and verify with `php artisan migrate:status`
4. **Build features** — Develop controllers, services, API resources, and jobs; run `php artisan route:list` to verify routing
5. **Test thoroughly** — Write feature and unit tests; run `php artisan test` before considering any step complete (target >85% coverage)

## Reference Guide

Load detailed guidance based on context:

| Topic | Reference | Load When |
|-------|-----------|-----------|
| API Reference | `references/api-reference.md` | Exact endpoint paths, request params, response schemas |
| Architecture | `references/architecture.md` | Module structure, data flow, patterns used |
| Eloquent ORM | `references/eloquent.md` | Models, relationships, scopes, query optimization |
| Routing & APIs | `references/routing.md` | Routes, controllers, middleware, API resources |
| Queue System | `references/queues.md` | Jobs, workers, failed jobs, batching |
| Testing | `references/testing.md` | Feature tests, factories, mocking, Pest PHP |

## Constraints

### MUST DO
- Use PHP 8.4+ features (readonly, enums, typed properties, property hooks)
- Type hint all method parameters and return types
- Use Eloquent relationships properly (avoid N+1 with eager loading)
- Implement API resources for transforming data
- Use `declare(strict_types=1)` in all PHP files
- Queue long-running tasks
- Write comprehensive tests (Pest, >85% coverage)
- Use service containers and dependency injection
- Follow PSR-12 coding standards (enforced by Pint)

### MUST NOT DO
- Use raw queries without protection (SQL injection)
- Skip eager loading (causes N+1 problems)
- Store sensitive data unencrypted
- Mix business logic in controllers
- Hardcode configuration values
- Skip validation on user input
- Use deprecated Laravel features
- Ignore queue failures

## Code Templates

Use these as starting points for every implementation.

### Eloquent Model

```php
<?php

declare(strict_types=1);

namespace Modules\Cms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['title', 'body', 'status', 'user_id'];

    protected $casts = [
        'status' => PostStatus::class,
        'published_at' => 'immutable_datetime',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PostStatus::Published);
    }
}
```

### Migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
```

### API Resource

```php
<?php

declare(strict_types=1);

namespace Modules\Cms\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'title'        => $this->title,
            'body'         => $this->body,
            'status'       => $this->status->value,
            'published_at' => $this->published_at?->toIso8601String(),
            'author'       => UserResource::make($this->whenLoaded('author')),
            'comments'     => CommentResource::collection($this->whenLoaded('comments')),
        ];
    }
}
```

### Queued Job

```php
<?php

declare(strict_types=1);

namespace Modules\Cms\Jobs;

use Modules\Cms\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class PublishPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        private readonly Post $post,
    ) {}

    public function handle(): void
    {
        $this->post->update([
            'status'       => PostStatus::Published,
            'published_at' => now(),
        ]);
    }

    public function failed(\Throwable $e): void
    {
        logger()->error('PublishPost failed', ['post' => $this->post->id, 'error' => $e->getMessage()]);
    }
}
```

### Feature Test (Pest)

```php
<?php

declare(strict_types=1);

use Modules\Cms\Models\Post;
use Modules\Cms\Models\User;

uses(Tests\RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('returns a published post for authenticated users', function (): void {
    $post = Post::factory()->published()->for($this->user, 'author')->create();

    $response = $this->getJson("/api/v1/posts/{$post->id}");

    $response->assertOk()
        ->assertJsonPath('data.status', 'published')
        ->assertJsonPath('data.author.id', $this->user->id);
});

it('queues a publish job when a draft is submitted', function (): void {
    Queue::fake();
    $post = Post::factory()->draft()->for($this->user, 'author')->create();

    $this->postJson("/api/v1/posts/{$post->id}/publish")
        ->assertAccepted();

    Queue::assertPushed(PublishPost::class, fn ($job) => $job->post->is($post));
});
```

## Validation Checkpoints

Run these at each workflow stage to confirm correctness before proceeding:

| Stage | Command | Expected Result |
|-------|---------|-----------------|
| After migration | `php artisan migrate:status` | All migrations show `Ran` |
| After routing | `php artisan route:list --path=api` | New routes appear with correct verbs |
| After job dispatch | `php artisan queue:work --once` | Job processes without exception |
| After implementation | `php artisan test --compact --coverage` | >85% coverage, 0 failures |
| Before PR | `./vendor/bin/pint --format agent && ./vendor/bin/phpstan analyse --memory-limit=512M` | Linting and static analysis pass |

## Knowledge Reference

Laravel 10+, Eloquent ORM, PHP 8.4+, API resources, Sanctum, queues, Pest, Redis, events/listeners, notifications, task scheduling

[Documentation](https://jeffallan.github.io/claude-skills/skills/backend/laravel-specialist/)
