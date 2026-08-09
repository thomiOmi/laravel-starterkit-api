<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use Illuminate\Support\Str;
use Modules\IAM\Database\Factories\RoleFactory;
use Modules\IAM\Database\Seeders\IAMSeeder;
use Modules\IAM\Models\Role;

describe('role admin endpoints', function (): void {
    beforeEach(function (): void {
        $this->seed(IAMSeeder::class);
    });

    describe('index', function (): void {
        it('returns a paginated list of roles for an admin', function (): void {
            loginAsAdmin();

            $response = $this->getJson('/api/v1/roles');

            assertPaginatedResponse($response);
            $response->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'description'],
                ],
            ]);
        })->group('module:iam');

        it('searches roles by name', function (): void {
            loginAsAdmin();
            RoleFactory::new()->createOne(['name' => 'needle-role']);

            $response = $this->getJson('/api/v1/roles?search=needle-role');

            assertSuccessResponse($response);
            expect($response->json('meta.total'))->toBe(1);
            expect($response->json('data.0.name'))->toBe('needle-role');
        })->group('module:iam');

        it('filters roles by name', function (): void {
            loginAsAdmin();
            RoleFactory::new()->createOne(['name' => 'needle-role']);

            $response = $this->getJson('/api/v1/roles?filter[name]=needle-role');

            expect($response->json('meta.total'))->toBe(1);
            expect($response->json('data.0.name'))->toBe('needle-role');
        })->group('module:iam');

        it('sorts roles by name', function (): void {
            loginAsAdmin();
            RoleFactory::new()->createOne(['name' => 'viewer-role']);
            RoleFactory::new()->createOne(['name' => 'editor-role']);

            $response = $this->getJson('/api/v1/roles?sort=name&search=-role');

            expect($response->json('data.0.name'))->toBe('editor-role');
            expect($response->json('data.1.name'))->toBe('viewer-role');
        })->group('module:iam');

        it('includes permissions when requested', function (): void {
            loginAsAdmin();
            $role = RoleFactory::new()->createOne(['name' => 'included-role']);
            $role->syncPermissions(['user.view']);

            $response = $this->getJson('/api/v1/roles?include=permissions&search=included-role');

            expect($response->json('data.0.permissions'))->toContain('user.view');
        })->group('module:iam');

        it('denies users without the view permission', function (): void {
            loginAsUserRole();

            $this->getJson('/api/v1/roles')->assertForbidden();
        })->group('module:iam');

        it('rejects unauthenticated requests', function (): void {
            $this->getJson('/api/v1/roles')->assertUnauthorized();
        })->group('module:iam');
    });

    describe('show', function (): void {
        it('returns a role with its permissions', function (): void {
            loginAsAdmin();
            $role = RoleFactory::new()->createOne();
            $role->syncPermissions(['user.view', 'user.edit']);

            $response = $this->getJson("/api/v1/roles/{$role->id}");

            assertSuccessResponse($response, 200, 'OK');
            expect($response->json('data.id'))->toBe($role->id);
            expect($response->json('data.permissions'))->toBe(['user.view', 'user.edit']);
        })->group('module:iam');

        it('returns 404 for a missing role', function (): void {
            loginAsAdmin();

            $this->getJson('/api/v1/roles/'.Str::ulid()->toString())->assertNotFound();
        })->group('module:iam');

        it('denies users without the view permission', function (): void {
            loginAsUserRole();
            $role = RoleFactory::new()->createOne();

            $this->getJson("/api/v1/roles/{$role->id}")->assertForbidden();
        })->group('module:iam');
    });

    describe('create', function (): void {
        it('creates a role with permissions', function (): void {
            loginAsAdmin();

            $response = $this->postJson('/api/v1/roles', [
                'name' => 'editor',
                'description' => 'Can edit content',
                'permissions' => ['user.view', 'user.edit'],
            ]);

            assertSuccessResponse($response, 201, 'Created');
            expect($response->json('data.name'))->toBe('editor');
            expect($response->json('data.permissions'))->toBe(['user.view', 'user.edit']);

            $role = Role::where('name', 'editor')->firstOrFail();
            expect($role->hasPermissionTo('user.edit'))->toBeTrue();
        })->group('module:iam');

        it('creates a role without permissions', function (): void {
            loginAsAdmin();

            $response = $this->postJson('/api/v1/roles', [
                'name' => 'viewer',
            ]);

            assertSuccessResponse($response, 201, 'Created');
            expect($response->json('data.permissions'))->toBe([]);
        })->group('module:iam');

        it('rejects a duplicate role name', function (): void {
            loginAsAdmin();
            RoleFactory::new()->createOne(['name' => 'editor']);

            $response = $this->postJson('/api/v1/roles', [
                'name' => 'editor',
            ]);

            assertProblemResponse($response, 422, 'validation');
            expect($response->json('errors.name'))->not->toBeNull();
        })->group('module:iam');

        it('rejects unknown permission names', function (): void {
            loginAsAdmin();

            $response = $this->postJson('/api/v1/roles', [
                'name' => 'broken',
                'permissions' => ['does.not.exist'],
            ]);

            assertProblemResponse($response, 422, 'validation');
            $response->assertJsonValidationErrors('permissions.0');
        })->group('module:iam');

        it('denies users without the create permission', function (): void {
            loginAsUserRole();

            $this->postJson('/api/v1/roles', ['name' => 'denied'])->assertForbidden();
        })->group('module:iam');
    });

    describe('update', function (): void {
        it('updates a role name and description', function (): void {
            loginAsAdmin();
            $role = RoleFactory::new()->createOne(['name' => 'old-name']);

            $response = $this->putJson("/api/v1/roles/{$role->id}", [
                'name' => 'new-name',
                'description' => 'Updated description',
            ]);

            assertSuccessResponse($response, 200, 'OK');
            expect($response->json('data.name'))->toBe('new-name');
            expect($role->refresh()->description)->toBe('Updated description');
        })->group('module:iam');

        it('syncs permissions to the new list on update', function (): void {
            loginAsAdmin();
            $role = RoleFactory::new()->createOne();
            $role->syncPermissions(['user.view']);

            $response = $this->putJson("/api/v1/roles/{$role->id}", [
                'name' => $role->name,
                'permissions' => ['role.view'],
            ]);

            assertSuccessResponse($response, 200, 'OK');
            expect($response->json('data.permissions'))->toBe(['role.view']);
            expect($role->hasPermissionTo('user.view'))->toBeFalse();
            expect($role->hasPermissionTo('role.view'))->toBeTrue();
        })->group('module:iam');

        it('leaves permissions untouched when the key is omitted', function (): void {
            loginAsAdmin();
            $role = RoleFactory::new()->createOne();
            $role->syncPermissions(['user.view']);

            $this->putJson("/api/v1/roles/{$role->id}", [
                'name' => $role->name,
            ]);

            expect($role->refresh()->hasPermissionTo('user.view'))->toBeTrue();
        })->group('module:iam');

        it('prevents non-super-admin actors from updating the super-admin role', function (): void {
            loginAsAdmin();
            $superAdminRole = Role::where('name', RoleEnum::SuperAdmin->value)->firstOrFail();

            $this->putJson("/api/v1/roles/{$superAdminRole->id}", [
                'name' => 'hacked',
            ])->assertForbidden();
        })->group('module:iam');

        it('denies users without the edit permission', function (): void {
            loginAsUserRole();
            $role = RoleFactory::new()->createOne();

            $this->putJson("/api/v1/roles/{$role->id}", ['name' => 'denied'])->assertForbidden();
        })->group('module:iam');
    });

    describe('delete', function (): void {
        it('deletes a role', function (): void {
            loginAsAdmin();
            $role = RoleFactory::new()->createOne();

            $response = $this->deleteJson("/api/v1/roles/{$role->id}");

            assertSuccessResponse($response, 200);
            expect(Role::find($role->id))->toBeNull();
        })->group('module:iam');

        it('protects the super-admin role from deletion', function (): void {
            loginAsSuperAdmin();
            $superAdminRole = Role::where('name', RoleEnum::SuperAdmin->value)->firstOrFail();

            $this->deleteJson("/api/v1/roles/{$superAdminRole->id}")->assertForbidden();
            expect(Role::where('name', RoleEnum::SuperAdmin->value)->exists())->toBeTrue();
        })->group('module:iam');

        it('denies users without the delete permission', function (): void {
            loginAsUserRole();
            $role = RoleFactory::new()->createOne();

            $this->deleteJson("/api/v1/roles/{$role->id}")->assertForbidden();
        })->group('module:iam');
    });

    describe('bulk delete', function (): void {
        it('deletes multiple roles and reports the count', function (): void {
            loginAsAdmin();
            $roles = RoleFactory::new()->count(2)->create();

            $response = $this->postJson('/api/v1/roles/bulk/delete', [
                'ids' => $roles->pluck('id')->all(),
            ]);

            assertSuccessResponse($response, 200, 'OK');
            expect($response->json('data.count'))->toBe(2);
            expect(Role::whereIn('id', $roles->pluck('id'))->exists())->toBeFalse();
        })->group('module:iam');

        it('excludes the super-admin role from the count', function (): void {
            loginAsSuperAdmin();
            $customRole = RoleFactory::new()->createOne();
            $superAdminRole = Role::where('name', RoleEnum::SuperAdmin->value)->firstOrFail();

            $response = $this->postJson('/api/v1/roles/bulk/delete', [
                'ids' => [$superAdminRole->id, $customRole->id],
            ]);

            expect($response->json('data.count'))->toBe(1);
            expect(Role::where('name', RoleEnum::SuperAdmin->value)->exists())->toBeTrue();
        })->group('module:iam');

        it('denies users without the delete permission', function (): void {
            loginAsUserRole();

            $this->postJson('/api/v1/roles/bulk/delete', [
                'ids' => [Str::ulid()->toString()],
            ])->assertForbidden();
        })->group('module:iam');
    });
});
