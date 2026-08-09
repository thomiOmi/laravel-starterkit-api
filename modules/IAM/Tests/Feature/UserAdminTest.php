<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\IAM\Database\Factories\UserFactory;
use Modules\IAM\Database\Seeders\IAMSeeder;
use Modules\IAM\Models\User;

describe('user admin endpoints', function (): void {
    beforeEach(function (): void {
        $this->seed(IAMSeeder::class);
    });

    describe('index', function (): void {
        it('returns a paginated list of users for an admin', function (): void {
            loginAsAdmin();

            $response = $this->getJson('/api/v1/users');

            assertPaginatedResponse($response);
            $response->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'email', 'status', 'status_label'],
                ],
            ]);
            expect($response->json('data.0.id'))->toBeString();
        })->group('module:iam');

        it('searches users by email', function (): void {
            loginAsAdmin();
            UserFactory::new()->createOne(['email' => 'needle@example.com']);

            $response = $this->getJson('/api/v1/users?search=needle@example.com');

            assertSuccessResponse($response);
            expect($response->json('meta.total'))->toBe(1);
            expect($response->json('data.0.email'))->toBe('needle@example.com');
        })->group('module:iam');

        it('searches users by name', function (): void {
            loginAsAdmin();
            UserFactory::new()->createOne(['name' => 'Needle Smith']);

            $response = $this->getJson('/api/v1/users?search=Needle');

            expect($response->json('meta.total'))->toBe(1);
            expect($response->json('data.0.name'))->toBe('Needle Smith');
        })->group('module:iam');

        it('filters users by status with an exact match', function (): void {
            loginAsAdmin();
            UserFactory::new()->banned()->createOne(['email' => 'banned@example.com']);

            $response = $this->getJson('/api/v1/users?filter[status]=banned');

            expect($response->json('meta.total'))->toBe(1);
            expect($response->json('data.0.status'))->toBe('banned');
        })->group('module:iam');

        it('filters users by role through the spatie named scope', function (): void {
            loginAsAdmin();
            UserFactory::new()->admin()->createOne();

            $response = $this->getJson('/api/v1/users?filter[role]=admin');

            expect($response->json('meta.total'))->toBeGreaterThanOrEqual(2);
            expect($response->json('data.0.roles'))->toContain('admin');
        })->group('module:iam');

        it('sorts users by creation date in descending order', function (): void {
            loginAsAdmin();
            $newer = UserFactory::new()->createOne([
                'email' => 'newer@example.com',
                'created_at' => now()->addHour(),
            ]);
            UserFactory::new()->createOne([
                'email' => 'older@example.com',
                'created_at' => now()->subHour(),
            ]);

            $response = $this->getJson('/api/v1/users?sort=-created_at&search=@example.com');

            expect($response->json('data.0.email'))->toBe($newer->email);
        })->group('module:iam');

        it('selects only the requested fields', function (): void {
            loginAsAdmin();
            UserFactory::new()->createOne(['email' => 'fieldtest@example.com']);

            $response = $this->getJson('/api/v1/users?fields[users]=id,name&search=fieldtest');

            expect($response->json('data.0.id'))->toBeString();
            expect($response->json('data.0.name'))->toBeString();
            expect($response->json('data.0.email'))->toBeNull();
        })->group('module:iam');

        it('includes roles and permissions when requested', function (): void {
            loginAsAdmin();
            UserFactory::new()->user()->createOne(['email' => 'include@example.com']);

            $response = $this->getJson('/api/v1/users?include=roles,permissions&search=include@example.com');

            expect($response->json('data.0.roles'))->toBe(['user']);
            expect($response->json('data.0.permissions'))->toBe([]);
        })->group('module:iam');

        it('paginates with the requested page size and number', function (): void {
            loginAsAdmin();

            $response = $this->getJson('/api/v1/users?page[size]=2&page[number]=2');

            assertPaginatedResponse($response);
            expect($response->json('meta.current_page'))->toBe(2);
            expect($response->json('meta.per_page'))->toBe(2);
            expect($response->json('meta.has_more'))->toBeTrue();
            expect($response->json('data'))->toHaveCount(2);
        })->group('module:iam');

        it('denies users without the view permission', function (): void {
            loginAsUser();

            $this->getJson('/api/v1/users')->assertForbidden();
        })->group('module:iam');

        it('rejects unauthenticated requests', function (): void {
            $this->getJson('/api/v1/users')->assertUnauthorized();
        })->group('module:iam');
    });

    describe('show', function (): void {
        it('returns a single user for an admin', function (): void {
            loginAsAdmin();
            $target = UserFactory::new()->createOne();

            $response = $this->getJson("/api/v1/users/{$target->id}");

            assertSuccessResponse($response, 200, 'OK');
            expect($response->json('data.id'))->toBe($target->id);
            expect($response->json('data.email'))->toBe($target->email);
        })->group('module:iam');

        it('allows a user to view their own profile without the view permission', function (): void {
            $user = loginAsUser();

            $this->getJson("/api/v1/users/{$user->id}")->assertOk();
        })->group('module:iam');

        it('denies viewing another user without the view permission', function (): void {
            loginAsUser();
            $target = UserFactory::new()->createOne();

            $this->getJson("/api/v1/users/{$target->id}")->assertForbidden();
        })->group('module:iam');

        it('returns 404 for a missing user', function (): void {
            loginAsAdmin();

            $this->getJson('/api/v1/users/'.Str::ulid()->toString())->assertNotFound();
        })->group('module:iam');
    });

    describe('create', function (): void {
        it('creates a user with the default user role', function (): void {
            loginAsAdmin();

            $response = $this->postJson('/api/v1/users', [
                'name' => 'New User',
                'email' => 'newuser@example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
            ]);

            assertSuccessResponse($response, 201, 'Created');
            expect($response->json('data.email'))->toBe('newuser@example.com');
            expect($response->json('data.status'))->toBe('pending');
            expect($response->json('data.roles'))->toBe(['user']);

            $user = User::where('email', 'newuser@example.com')->firstOrFail();
            expect($user->hasRole(RoleEnum::User))->toBeTrue();
            expect(Hash::check('secret-password', (string) $user->password))->toBeTrue();
        })->group('module:iam');

        it('creates a user with an explicit status', function (): void {
            loginAsAdmin();

            $response = $this->postJson('/api/v1/users', [
                'name' => 'Banned User',
                'email' => 'banned@example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
                'status' => 'banned',
            ]);

            assertSuccessResponse($response, 201, 'Created');
            expect($response->json('data.status'))->toBe('banned');
            expect(User::where('email', 'banned@example.com')->firstOrFail()->status->value)->toBe('banned');
        })->group('module:iam');

        it('rejects a duplicate email', function (): void {
            loginAsAdmin();
            UserFactory::new()->createOne(['email' => 'taken@example.com']);

            $response = $this->postJson('/api/v1/users', [
                'name' => 'Duplicate',
                'email' => 'taken@example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
            ]);

            assertProblemResponse($response, 422, 'validation');
            expect($response->json('errors.email'))->not->toBeNull();
        })->group('module:iam');

        it('rejects a mismatched password confirmation', function (): void {
            loginAsAdmin();

            $response = $this->postJson('/api/v1/users', [
                'name' => 'Mismatch',
                'email' => 'mismatch@example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'different-password',
            ]);

            assertProblemResponse($response, 422, 'validation');
            expect($response->json('errors.password'))->not->toBeNull();
        })->group('module:iam');

        it('denies users without the create permission', function (): void {
            loginAsUser();

            $this->postJson('/api/v1/users', [
                'name' => 'Denied',
                'email' => 'denied@example.com',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
            ])->assertForbidden();
        })->group('module:iam');
    });

    describe('update', function (): void {
        it('updates a user name and email', function (): void {
            loginAsAdmin();
            $target = UserFactory::new()->createOne();

            $response = $this->putJson("/api/v1/users/{$target->id}", [
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
            ]);

            assertSuccessResponse($response, 200, 'OK');
            expect($response->json('data.name'))->toBe('Updated Name');
            expect($target->refresh()->email)->toBe('updated@example.com');
        })->group('module:iam');

        it('ignores the unique rule for the user own email', function (): void {
            loginAsAdmin();
            $target = UserFactory::new()->createOne();

            $response = $this->putJson("/api/v1/users/{$target->id}", [
                'name' => $target->name,
                'email' => $target->email,
            ]);

            assertSuccessResponse($response, 200, 'OK');
        })->group('module:iam');

        it('rejects updating to another user email', function (): void {
            loginAsAdmin();
            $target = UserFactory::new()->createOne();
            $other = UserFactory::new()->createOne();

            $response = $this->putJson("/api/v1/users/{$target->id}", [
                'name' => $target->name,
                'email' => $other->email,
            ]);

            assertProblemResponse($response, 422, 'validation');
            expect($response->json('errors.email'))->not->toBeNull();
        })->group('module:iam');

        it('prevents non-super-admin actors from updating a super admin', function (): void {
            loginAsAdmin();
            $superAdmin = UserFactory::new()->superAdmin()->createOne();

            $this->putJson("/api/v1/users/{$superAdmin->id}", [
                'name' => 'Hacked Name',
                'email' => $superAdmin->email,
            ])->assertForbidden();
        })->group('module:iam');

        it('denies non-admin actors from updating another user', function (): void {
            loginAsUserRole();
            $target = UserFactory::new()->createOne();

            $this->putJson("/api/v1/users/{$target->id}", [
                'name' => 'Sneaky',
                'email' => $target->email,
            ])->assertForbidden();
        })->group('module:iam');
    });

    describe('delete', function (): void {
        it('soft deletes a user', function (): void {
            loginAsAdmin();
            $target = UserFactory::new()->createOne();

            $response = $this->deleteJson("/api/v1/users/{$target->id}");

            assertSuccessResponse($response, 200);
            expect($target->refresh()->trashed())->toBeTrue();
        })->group('module:iam');

        it('prevents deleting yourself', function (): void {
            $admin = loginAsAdmin();

            $this->deleteJson("/api/v1/users/{$admin->id}")->assertForbidden();
        })->group('module:iam');

        it('prevents non-super-admin actors from deleting a super admin', function (): void {
            loginAsAdmin();
            $superAdmin = UserFactory::new()->superAdmin()->createOne();

            $this->deleteJson("/api/v1/users/{$superAdmin->id}")->assertForbidden();
        })->group('module:iam');

        it('denies users without the delete permission', function (): void {
            loginAsUserRole();
            $target = UserFactory::new()->createOne();

            $this->deleteJson("/api/v1/users/{$target->id}")->assertForbidden();
        })->group('module:iam');
    });

    describe('bulk delete', function (): void {
        it('soft deletes multiple users and reports the count', function (): void {
            loginAsAdmin();
            $targetA = UserFactory::new()->createOne();
            $targetB = UserFactory::new()->createOne();

            $response = $this->postJson('/api/v1/users/bulk/delete', [
                'ids' => [$targetA->id, $targetB->id],
            ]);

            assertSuccessResponse($response, 200, 'OK');
            expect($response->json('data.count'))->toBe(2);
            expect($targetA->refresh()->trashed())->toBeTrue();
            expect($targetB->refresh()->trashed())->toBeTrue();
        })->group('module:iam');

        it('excludes the actor from the bulk delete', function (): void {
            $admin = loginAsAdmin();
            $target = UserFactory::new()->createOne();

            $response = $this->postJson('/api/v1/users/bulk/delete', [
                'ids' => [$admin->id, $target->id],
            ]);

            expect($response->json('data.count'))->toBe(1);
            expect($admin->refresh()->trashed())->toBeFalse();
            expect($target->refresh()->trashed())->toBeTrue();
        })->group('module:iam');

        it('excludes super admins when the actor is not a super admin', function (): void {
            loginAsAdmin();
            $superAdmin = UserFactory::new()->superAdmin()->createOne();
            $target = UserFactory::new()->createOne();

            $response = $this->postJson('/api/v1/users/bulk/delete', [
                'ids' => [$superAdmin->id, $target->id],
            ]);

            expect($response->json('data.count'))->toBe(1);
            expect($superAdmin->refresh()->trashed())->toBeFalse();
            expect($target->refresh()->trashed())->toBeTrue();
        })->group('module:iam');

        it('denies users without the delete permission', function (): void {
            loginAsUserRole();

            $this->postJson('/api/v1/users/bulk/delete', [
                'ids' => [Str::ulid()->toString()],
            ])->assertForbidden();
        })->group('module:iam');

        it('rejects an empty ids payload', function (): void {
            loginAsAdmin();

            $this->postJson('/api/v1/users/bulk/delete', ['ids' => []])->assertUnprocessable();
        })->group('module:iam');
    });

    describe('bulk restore', function (): void {
        it('restores soft-deleted users and reports the count', function (): void {
            loginAsAdmin();
            $targetA = UserFactory::new()->createOne();
            $targetB = UserFactory::new()->createOne();
            $targetA->delete();
            $targetB->delete();

            $response = $this->postJson('/api/v1/users/bulk/restore', [
                'ids' => [$targetA->id, $targetB->id],
            ]);

            assertSuccessResponse($response, 200, 'OK');
            expect($response->json('data.count'))->toBe(2);
            expect($targetA->refresh()->trashed())->toBeFalse();
            expect($targetB->refresh()->trashed())->toBeFalse();
        })->group('module:iam');

        it('excludes super admins when the actor is not a super admin', function (): void {
            loginAsAdmin();
            $superAdmin = UserFactory::new()->superAdmin()->createOne();
            $superAdmin->delete();

            $response = $this->postJson('/api/v1/users/bulk/restore', [
                'ids' => [$superAdmin->id],
            ]);

            expect($response->json('data.count'))->toBe(0);
            expect($superAdmin->refresh()->trashed())->toBeTrue();
        })->group('module:iam');

        it('denies users without the restore permission', function (): void {
            loginAsUserRole();

            $this->postJson('/api/v1/users/bulk/restore', [
                'ids' => [Str::ulid()->toString()],
            ])->assertForbidden();
        })->group('module:iam');
    });
});
