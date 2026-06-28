<?php

declare(strict_types=1);

use Modules\Role\Database\Seeders\RoleSeeder;
use Modules\User\Actions\DeleteUserAction;
use Modules\User\Models\User;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super-admin');
    $this->actingAs($this->admin);
});

describe('DeleteUserAction', function () {
    it('deletes another user', function () {
        $user = User::factory()->create();

        $action = app(DeleteUserAction::class);
        $result = $action->handle((string) $user->id);

        expect($result)->toBeTrue();
        expect($user->fresh()->trashed())->toBeTrue();
    });

    it('prevents self-deletion', function () {
        $action = app(DeleteUserAction::class);
        $result = $action->handle((string) $this->admin->id);

        expect($result)->toBeFalse();
        expect($this->admin->fresh()->trashed())->toBeFalse();
    });

    it('returns false for non-existent user', function () {
        $action = app(DeleteUserAction::class);
        $result = $action->handle('non-existent');

        expect($result)->toBeFalse();
    });
});
