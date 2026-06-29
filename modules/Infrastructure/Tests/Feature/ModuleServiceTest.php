<?php

declare(strict_types=1);

namespace Modules\Infrastructure\Tests\Feature;

use Mockery;

/**
 * Dummy Service for Contract Testing
 * This represents the standard "Action" or "Service" pattern in the application.
 */
abstract class AbstractModuleService
{
    /**
     * Common execution pattern for modular services.
     */
    abstract public function handle(mixed $payload): mixed;
}

/**
 * Concrete Implementation for Testing
 */
class MockModuleService extends AbstractModuleService
{
    public function __construct(
        protected mixed $repository
    ) {}

    public function handle(mixed $payload): mixed
    {
        // Simple logic using repository
        return $this->repository->findById((string) $payload);
    }
}

test('ModuleService follows abstract contract and supports mocking', function () {
    // Mocking a repository interface/class
    $mockRepository = Mockery::mock('UserRepository');
    $mockRepository->shouldReceive('findById')
        ->once()
        ->with('ulid-123')
        ->andReturn(['id' => 'ulid-123', 'name' => 'John Doe']);

    // Injecting mock into service
    $service = new MockModuleService($mockRepository);

    // Executing service
    $result = $service->handle('ulid-123');

    // Expectations
    expect($result)->toBeArray()
        ->and($result['name'])->toBe('John Doe');
});

test('ModuleService can be resolved from container with mocks', function () {
    $mockRepository = Mockery::mock('UserRepository');

    // Bind mock to container
    $this->app->instance('UserRepository', $mockRepository);

    $mockRepository->shouldReceive('findById')
        ->andReturn(['id' => '1']);

    $service = $this->app->make(MockModuleService::class);

    expect($service->handle('1'))->toBe(['id' => '1']);
});
