<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\IAM\Database\Seeders\IAMSeeder;
use Modules\IAM\Models\Permission;

describe('permission admin endpoints', function (): void {
    beforeEach(function (): void {
        $this->seed(IAMSeeder::class);
    });

    describe('index', function (): void {
        it('returns a paginated list of permissions for an admin', function (): void {
            loginAsAdmin();

            $response = $this->getJson('/api/v1/permissions');

            assertPaginatedResponse($response);
            $response->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name'],
                ],
            ]);
        })->group('module:iam');

        it('searches permissions by name', function (): void {
            loginAsAdmin();

            $response = $this->getJson('/api/v1/permissions?search=user.view');

            assertSuccessResponse($response);
            expect($response->json('meta.total'))->toBe(1);
            expect($response->json('data.0.name'))->toBe('user.view');
        })->group('module:iam');

        it('filters permissions by name', function (): void {
            loginAsAdmin();

            $response = $this->getJson('/api/v1/permissions?filter[name]=user.view');

            expect($response->json('meta.total'))->toBe(1);
            expect($response->json('data.0.name'))->toBe('user.view');
        })->group('module:iam');

        it('denies users without the view permission', function (): void {
            loginAsUserRole();

            $this->getJson('/api/v1/permissions')->assertForbidden();
        })->group('module:iam');

        it('rejects unauthenticated requests', function (): void {
            $this->getJson('/api/v1/permissions')->assertUnauthorized();
        })->group('module:iam');
    });

    describe('show', function (): void {
        it('returns a single permission for an admin', function (): void {
            loginAsAdmin();
            $permission = Permission::where('name', 'user.view')->firstOrFail();

            $response = $this->getJson("/api/v1/permissions/{$permission->id}");

            assertSuccessResponse($response, 200, 'OK');
            expect($response->json('data.id'))->toBe($permission->id);
            expect($response->json('data.name'))->toBe('user.view');
        })->group('module:iam');

        it('returns 404 for a missing permission', function (): void {
            loginAsAdmin();

            $this->getJson('/api/v1/permissions/'.Str::ulid()->toString())->assertNotFound();
        })->group('module:iam');

        it('denies users without the view permission', function (): void {
            loginAsUserRole();
            $permission = Permission::where('name', 'user.view')->firstOrFail();

            $this->getJson("/api/v1/permissions/{$permission->id}")->assertForbidden();
        })->group('module:iam');
    });

    describe('create', function (): void {
        it('creates a permission', function (): void {
            loginAsAdmin();

            $response = $this->postJson('/api/v1/permissions', [
                'name' => 'report.view',
            ]);

            assertSuccessResponse($response, 201, 'Created');
            expect($response->json('data.name'))->toBe('report.view');
            expect(Permission::where('name', 'report.view')->exists())->toBeTrue();
        })->group('module:iam');

        it('rejects a duplicate permission name', function (): void {
            loginAsAdmin();

            $response = $this->postJson('/api/v1/permissions', [
                'name' => 'user.view',
            ]);

            assertProblemResponse($response, 422, 'validation');
            expect($response->json('errors.name'))->not->toBeNull();
        })->group('module:iam');

        it('denies users without the create permission', function (): void {
            loginAsUserRole();

            $this->postJson('/api/v1/permissions', ['name' => 'denied.view'])->assertForbidden();
        })->group('module:iam');
    });

    describe('update', function (): void {
        it('renames a permission', function (): void {
            loginAsAdmin();
            $permission = Permission::where('name', 'user.view')->firstOrFail();

            $response = $this->putJson("/api/v1/permissions/{$permission->id}", [
                'name' => 'user.view.renamed',
            ]);

            assertSuccessResponse($response, 200, 'OK');
            expect($response->json('data.name'))->toBe('user.view.renamed');
            expect($permission->refresh()->name)->toBe('user.view.renamed');
        })->group('module:iam');

        it('rejects renaming to an existing permission name', function (): void {
            loginAsAdmin();
            $permission = Permission::where('name', 'user.view')->firstOrFail();

            $response = $this->putJson("/api/v1/permissions/{$permission->id}", [
                'name' => 'role.view',
            ]);

            assertProblemResponse($response, 422, 'validation');
            expect($response->json('errors.name'))->not->toBeNull();
        })->group('module:iam');

        it('denies users without the edit permission', function (): void {
            loginAsUserRole();
            $permission = Permission::where('name', 'user.view')->firstOrFail();

            $this->putJson("/api/v1/permissions/{$permission->id}", ['name' => 'denied.view'])->assertForbidden();
        })->group('module:iam');
    });

    describe('delete', function (): void {
        it('deletes a permission', function (): void {
            loginAsAdmin();
            $permission = Permission::where('name', 'user.view')->firstOrFail();

            $response = $this->deleteJson("/api/v1/permissions/{$permission->id}");

            assertSuccessResponse($response, 200);
            expect(Permission::find($permission->id))->toBeNull();
        })->group('module:iam');

        it('denies users without the delete permission', function (): void {
            loginAsUserRole();
            $permission = Permission::where('name', 'user.view')->firstOrFail();

            $this->deleteJson("/api/v1/permissions/{$permission->id}")->assertForbidden();
        })->group('module:iam');
    });
});
