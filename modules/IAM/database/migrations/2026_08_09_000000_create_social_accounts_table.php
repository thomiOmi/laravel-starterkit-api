<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->string('provider_id');
            $table->string('avatar')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_id']);
            $table->unique(['user_id', 'provider']);
        });

        $this->moveLegacyBindings();

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['provider', 'provider_id']);
            $table->dropColumn(['provider', 'provider_id']);
        });
    }

    /**
     * Copy the legacy single-provider bindings into the pivot table.
     */
    private function moveLegacyBindings(): void
    {
        DB::table('social_accounts')->insertUsing(
            ['id', 'user_id', 'provider', 'provider_id', 'avatar', 'created_at', 'updated_at'],
            DB::table('users')
                ->select('id', 'id', 'provider', 'provider_id', 'avatar', 'created_at', 'updated_at')
                ->whereNotNull('provider')
                ->whereNotNull('provider_id')
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('provider')->nullable()->after('password');
            $table->string('provider_id')->nullable()->after('provider');
            $table->index(['provider', 'provider_id']);
        });

        Schema::dropIfExists('social_accounts');
    }
};
