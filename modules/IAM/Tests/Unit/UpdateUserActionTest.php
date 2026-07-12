<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\IAM\Actions\UpdateUserAction;
use Modules\IAM\Models\User;
use Modules\IAM\Payloads\V1\UserPayload;

/**
 * Unit test for UpdateUserAction.
 */
describe('UpdateUserAction', function () {
    it('successfully updates a user model', function () {
        $user = User::factory()->create(['name' => 'Old Name']);
        $payload = new UserPayload(name: 'Updated Name', email: $user->email);

        $action = app(UpdateUserAction::class);
        $result = $action->handle((string) $user->id, $payload);

        expect($result)->toBeInstanceOf(User::class)
            ->name->toBe('Updated Name');
    });

    it('throws exception when user is not found', function () {
        $payload = new UserPayload(name: 'Ghost', email: 'ghost@test.com');
        $action = app(UpdateUserAction::class);

        expect(fn () => $action->handle('non-existent-id', $payload))->toThrow(ModelNotFoundException::class);
    });
});
