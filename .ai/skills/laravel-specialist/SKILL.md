---
name: laravel-specialist
description: Expert in Laravel 13, PHP 8.4, and Modular Monolith. Handles Eloquent models with Property Hooks, modular routing, and feature implementation using Action-Payload patterns.
license: MIT
metadata:
  version: "2.2.0"
---

# Laravel Specialist (Modular Monolith)

Expert guidance for building modern, scalable Laravel 13 applications.

## Core Workflow: Module Implementation
1. **Explore Schema:** Use `database-schema` (MCP) to understand existing tables.
2. **Create Module Component:** Work within `Modules/{ModuleName}/`.
3. **Action-Payload:**
   - Define a `final readonly` Payload in `Payloads/`.
   - Define a `final readonly` Action in `Actions/`.
   - Inject the Action into a Controller.
4. **Model Enrichment:** Use Property Hooks for logic.

## Code Templates

### 1. Model with Property Hooks & Traits
```php
<?php

declare(strict_types=1);

namespace Modules\Cms\Models;

use App\Concerns\HasDefaultBehavior;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Post extends Model
{
    use HasDefaultBehavior, SoftDeletes;

    /**
     * PHP 8.4 Property Hooks for calculated/mutated fields.
     */
    public string $slug {
        set(string $value) => str($value)->slug()->toString();
        get => $this->slug;
    }

    public string $excerpt {
        get => str($this->content)->limit(150)->toString();
    }

    protected $fillable = ['title', 'content', 'slug', 'user_id'];
}
```

### 2. Action with Payload (Production Grade)
```php
<?php

declare(strict_types=1);

namespace Modules\Cms\Actions;

use Modules\Cms\Models\Post;
use Modules\Cms\Payloads\CreatePostPayload;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class CreatePostAction
{
    public function handle(CreatePostPayload $payload): Post
    {
        return DB::transaction(function () use ($payload): Post {
            $post = Post::create($payload->toArray());

            Log::channel('cms')->info('Post created', ['id' => $post->id]);

            return $post;
        });
    }
}
```

### 3. Modular Controller
```php
<?php

declare(strict_types=1);

namespace Modules\Cms\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\SuccessResponse;
use Modules\Cms\Actions\CreatePostAction;
use Modules\Cms\Requests\StorePostRequest;
use Modules\Cms\Resources\PostResource;

final readonly class StorePostController extends Controller
{
    public function __invoke(StorePostRequest $request, CreatePostAction $action): SuccessResponse
    {
        // Convert FormRequest to Payload
        $payload = $request->toPayload();

        $post = $action->handle($payload);

        return new SuccessResponse(new PostResource($post));
    }
}
```

## Constraints
- **MUST** use `final` for all classes.
- **MUST** use Property Hooks instead of traditional getters/setters.
- **MUST NOT** import models from other modules. Use Contracts.
- **MUST** run `./vendor/bin/pint --format agent` before finishing.
