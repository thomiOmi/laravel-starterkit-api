<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure;

use Modules\IAM\Models\User;

test('ModuleService supports mocking via anonymous implementation', function () {
    $user = User::factory()->create(['name' => 'Jane Doe']);

    $service = new class
    {
        public function handle(mixed $payload): mixed
        {
            return User::query()->find($payload);
        }
    };

    $result = $service->handle($user->id);

    expect($result)->toBeObject()
        ->and($result->name)->toBe('Jane Doe');
});
