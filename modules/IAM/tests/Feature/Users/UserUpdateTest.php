<?php

declare(strict_types=1);

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Enums\UserStatusEnum;
use App\Notifications\VerifyEmail as VerifyEmailNotification;
use Illuminate\Support\Facades\Notification;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Http\Controllers\V1\UserUpdateController;
use Modules\IAM\Models\Permission;
use Modules\IAM\Models\Role;

covers(UserUpdateController::class);

describe('PUT /api/v1/users/{user}', function (): void {
    beforeEach(function (): void {
        Permission::query()->firstOrCreate(['name' => PermissionEnum::UserEdit->value, 'guard_name' => 'sanctum']);
        Role::query()->firstOrCreate(['name' => RoleEnum::SuperAdmin->value, 'guard_name' => 'sanctum']);
        Role::query()->firstOrCreate(['name' => RoleEnum::User->value, 'guard_name' => 'sanctum']);
    });

    it('allows self updates without permission', function (): void {
        $user = loginAsUser();

        $response = $this->putJson("/api/v1/users/{$user->id}", ['name' => 'Renamed Self', 'email' => $user->email]);

        assertSuccessResponse($response, 200);
        expect($response->json('data.name'))->toBe('Renamed Self');
    });

    it('prohibits self updates from changing the password', function (): void {
        $user = loginAsUser();
        $originalHash = $user->password;

        $response = $this->putJson("/api/v1/users/{$user->id}", [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        assertProblemResponse($response, 422, 'validation');
        $response->assertJsonValidationErrors(['password']);
        expect($user->refresh()->password)->toBe($originalHash);
    });

    it('prohibits editors from changing another user password', function (): void {
        $editor = loginAsUser();
        $editor->givePermissionTo(PermissionEnum::UserEdit->value);

        $target = UserFactory::new()->createOne();
        $originalHash = $target->password;

        $response = $this->putJson("/api/v1/users/{$target->id}", [
            'name' => $target->name,
            'email' => $target->email,
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        assertProblemResponse($response, 422, 'validation');
        $response->assertJsonValidationErrors(['password']);
        expect($target->refresh()->password)->toBe($originalHash);
    });

    it('resets verification and sends a notification when the email changes', function (): void {
        Notification::fake();
        $user = loginAsUser();

        $response = $this->putJson("/api/v1/users/{$user->id}", [
            'name' => $user->name,
            'email' => 'changed-self@example.com',
        ]);

        assertSuccessResponse($response, 200);
        $user->refresh();
        expect($user->email)->toBe('changed-self@example.com')
            ->and($user->email_verified_at)->toBeNull()
            ->and($user->status)->toBe(UserStatusEnum::Active);
        Notification::assertSentTo($user, VerifyEmailNotification::class);
    });

    it('resets verification and notifies when an editor changes another user email', function (): void {
        Notification::fake();
        $editor = loginAsUser();
        $editor->givePermissionTo(PermissionEnum::UserEdit->value);

        $target = UserFactory::new()->createOne();
        $originalStatus = $target->status;

        $response = $this->putJson("/api/v1/users/{$target->id}", [
            'name' => $target->name,
            'email' => 'changed-other@example.com',
        ]);

        assertSuccessResponse($response, 200);
        $target->refresh();
        expect($target->email)->toBe('changed-other@example.com')
            ->and($target->email_verified_at)->toBeNull()
            ->and($target->status)->toBe($originalStatus);
        Notification::assertSentTo($target, VerifyEmailNotification::class);
    });

    it('keeps verification and sends nothing when the normalized email is unchanged', function (): void {
        Notification::fake();
        $user = loginAsUser();
        $originalEmail = $user->email;
        $verifiedAt = $user->email_verified_at?->toDateTimeString();

        $response = $this->putJson("/api/v1/users/{$user->id}", [
            'name' => $user->name,
            'email' => mb_strtoupper($user->email),
        ]);

        assertSuccessResponse($response, 200);
        $user->refresh();
        expect($user->email)->toBe($originalEmail)
            ->and($user->email_verified_at?->toDateTimeString())->toBe($verifiedAt);
        Notification::assertNothingSent();
    });

    it('permits editors to update others', function (): void {
        $editor = loginAsUser();
        $editor->givePermissionTo(PermissionEnum::UserEdit->value);

        $target = UserFactory::new()->createOne();

        assertSuccessResponse(
            $this->putJson("/api/v1/users/{$target->id}", ['name' => 'Edited', 'email' => $target->email]),
            200
        );
    });

    it('blocks editing super-admins even with permission', function (): void {
        $editor = loginAsUser();
        $editor->givePermissionTo(PermissionEnum::UserEdit->value);

        $superAdmin = UserFactory::new()->superAdmin()->createOne();

        assertProblemResponse($this->putJson("/api/v1/users/{$superAdmin->id}", ['name' => 'Hijack']), 403);
    });

    it('prohibits status changes without the edit permission', function (): void {
        $user = loginAsUser();

        $response = $this->putJson("/api/v1/users/{$user->id}", [
            'name' => $user->name,
            'status' => UserStatusEnum::Banned->value,
        ]);

        assertProblemResponse($response, 422, 'validation');
        $response->assertJsonValidationErrors(['status']);
    });
});
