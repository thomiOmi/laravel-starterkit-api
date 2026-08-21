<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use Database\Seeders\DatabaseSeeder;
use Modules\IAM\Models\Role;
use Modules\IAM\Models\User;
use Nwidart\Modules\Contracts\RepositoryInterface;
use Nwidart\Modules\Module;

test('DatabaseSeeder seeds every enabled module that ships a seeder', function () {
    $repository = Mockery::mock(RepositoryInterface::class);
    $repository->shouldReceive('allEnabled')->andReturn([
        Mockery::mock(Module::class)->shouldReceive('getStudlyName')->andReturn('IAM')->getMock(),
        Mockery::mock(Module::class)->shouldReceive('getStudlyName')->andReturn('Ghost')->getMock(),
    ]);
    $this->app->instance(RepositoryInterface::class, $repository);

    (new DatabaseSeeder)->run();

    expect(User::where('email', 'superadmin@example.com')->exists())->toBeTrue()
        ->and(Role::where('name', RoleEnum::SuperAdmin->value)->exists())->toBeTrue();
});
