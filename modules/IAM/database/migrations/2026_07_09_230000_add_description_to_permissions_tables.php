<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @return array{permissions: string, roles: string} */
    private static function tableNames(): array
    {
        return [
            'permissions' => config()->string('permission.table_names.permissions', 'permissions'),
            'roles' => config()->string('permission.table_names.roles', 'roles'),
        ];
    }

    public function up(): void
    {
        $tableNames = self::tableNames();

        if (! Schema::hasColumn($tableNames['permissions'], 'description')) {
            Schema::table($tableNames['permissions'], static function (Blueprint $table): void {
                $table->text('description')->nullable();
            });
        }
    }

    public function down(): void
    {
        $tableNames = self::tableNames();

        Schema::table($tableNames['permissions'], static function (Blueprint $table): void {
            $table->dropColumn('description');
        });
    }
};
