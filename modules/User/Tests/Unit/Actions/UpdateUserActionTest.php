<?php

declare(strict_types=1);

use Modules\Role\Database\Seeders\RoleSeeder;
use Modules\User\Actions\UpdateUserAction;
use Modules\User\Models\User;
use Modules\User\Payloads\V1\UserPayload;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

describe('UpdateUserAction', function () {
    it('updates a user', function () {
        $user = User::factory()->create(['name' => 'Old Name']);

        $payload = new UserPayload(name: 'New Name', email: $user->email);
        $action = app(UpdateUserAction::class);
        $updated = $action->handle((string) $user->id, $payload);

        expect($updated->name)->toBe('New Name');
        expect($updated->id)->toBe($user->id);
    });

    it('returns null for non-existent user', function () {
        $payload = new UserPayload(name: 'Ghost', email: 'ghost@test.com');

        $action = app(UpdateUserAction::class);
        $result = $action->handle('non-existent', $payload);

        expect($result)->toBeNull();
    });
});
