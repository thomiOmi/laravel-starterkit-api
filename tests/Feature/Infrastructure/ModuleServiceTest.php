<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure;

use Mockery;
use Tests\TestCase;

test('ModuleService supports mocking via anonymous implementation', function () {
    /** @var TestCase $this */
    /** @var TestCase $this */
    $mockRepository = Mockery::mock('UserRepository');
    $mockRepository->shouldReceive('findById')
        ->once()
        ->with('ulid-123')
        ->andReturn(['id' => 'ulid-123', 'name' => 'John Doe']);

    $service = new class($mockRepository)
    {
        public function __construct(protected mixed $repository) {}

        public function handle(mixed $payload): mixed
        {
            return $this->repository->findById((string) $payload);
        }
    };

    $result = $service->handle('ulid-123');

    expect($result)->toBeArray()
        ->and($result['name'])->toBe('John Doe');
});
