<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /** @var array{roles: string, permissions: string, model_has_permissions: string, model_has_roles: string, role_has_permissions: string} $tableNames */
        $tableNames = config()->array('permission.table_names');

        /** @var array{role_pivot_key: string|null, permission_pivot_key: string|null, model_morph_key: string, team_foreign_key: string} $columnNames */
        $columnNames = config()->array('permission.column_names');

        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';

        $this->createPermissionsTable($tableNames['permissions']);
        $this->createRolesTable($tableNames['roles']);
        $this->createModelHasPermissionsTable(
            $tableNames['model_has_permissions'],
            $columnNames['model_morph_key'],
            $tableNames['permissions'],
            $pivotPermission,
        );
        $this->createModelHasRolesTable(
            $tableNames['model_has_roles'],
            $columnNames['model_morph_key'],
            $tableNames['roles'],
            $pivotRole,
        );
        $this->createRoleHasPermissionsTable(
            $tableNames['role_has_permissions'],
            $tableNames['permissions'],
            $tableNames['roles'],
            $pivotPermission,
            $pivotRole,
        );

        $cacheStore = config()->string('permission.cache.store', 'default');

        app('cache')
            ->store($cacheStore !== 'default' ? $cacheStore : null)
            ->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        /** @var array{roles: string, permissions: string, model_has_permissions: string, model_has_roles: string, role_has_permissions: string} $tableNames */
        $tableNames = config()->array('permission.table_names');

        Schema::dropIfExists($tableNames['role_has_permissions']);
        Schema::dropIfExists($tableNames['model_has_roles']);
        Schema::dropIfExists($tableNames['model_has_permissions']);
        Schema::dropIfExists($tableNames['roles']);
        Schema::dropIfExists($tableNames['permissions']);
    }

    private function createPermissionsTable(string $tableName): void
    {
        Schema::create($tableName, static function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
        });
    }

    private function createRolesTable(string $tableName): void
    {
        Schema::create($tableName, static function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('guard_name');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
        });
    }

    private function createModelHasPermissionsTable(
        string $tableName,
        string $modelMorphKey,
        string $permissionsTable,
        string $pivotPermission,
    ): void {
        Schema::create($tableName, static function (Blueprint $table) use ($pivotPermission, $modelMorphKey, $permissionsTable): void {
            $table->ulid($pivotPermission);

            $table->string('model_type');
            $table->ulid($modelMorphKey);

            $table->index([$modelMorphKey, 'model_type'], 'model_has_permissions_model_id_model_type_index');

            $table->foreign($pivotPermission)
                ->references('id')
                ->on($permissionsTable)
                ->cascadeOnDelete();

            $table->primary([$pivotPermission, $modelMorphKey, 'model_type'],
                'model_has_permissions_permission_model_type_primary');
        });
    }

    private function createModelHasRolesTable(
        string $tableName,
        string $modelMorphKey,
        string $rolesTable,
        string $pivotRole,
    ): void {
        Schema::create($tableName, static function (Blueprint $table) use ($pivotRole, $modelMorphKey, $rolesTable): void {
            $table->ulid($pivotRole);

            $table->string('model_type');
            $table->ulid($modelMorphKey);

            $table->index([$modelMorphKey, 'model_type'], 'model_has_roles_model_id_model_type_index');

            $table->foreign($pivotRole)
                ->references('id')
                ->on($rolesTable)
                ->cascadeOnDelete();

            $table->primary([$pivotRole, $modelMorphKey, 'model_type'],
                'model_has_roles_role_model_type_primary');
        });
    }

    private function createRoleHasPermissionsTable(
        string $tableName,
        string $permissionsTable,
        string $rolesTable,
        string $pivotPermission,
        string $pivotRole,
    ): void {
        Schema::create($tableName, static function (Blueprint $table) use ($pivotPermission, $pivotRole, $permissionsTable, $rolesTable): void {
            $table->ulid($pivotPermission);
            $table->ulid($pivotRole);

            $table->foreign($pivotPermission)
                ->references('id')
                ->on($permissionsTable)
                ->cascadeOnDelete();

            $table->foreign($pivotRole)
                ->references('id')
                ->on($rolesTable)
                ->cascadeOnDelete();

            $table->primary([$pivotPermission, $pivotRole], 'role_has_permissions_permission_id_role_id_primary');
        });
    }
};
