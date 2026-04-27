<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('webhooks', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('tenant_id');
            $table->string('name');
            $table->string('url');
            $table->string('secret')->nullable();
            $table->json('events'); // array of events like ['user.created', 'user.updated']
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index('tenant_id');
        });

        Schema::create('webhook_calls', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('tenant_id');
            $table->foreignUlid('webhook_id')->constrained('webhooks')->onDelete('cascade');
            $table->string('event');
            $table->json('payload');
            $table->integer('status_code')->nullable();
            $table->text('response_body')->nullable();
            $table->string('status')->default('pending'); // pending, success, failed
            $table->integer('tries')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_calls');
        Schema::dropIfExists('webhooks');
    }
};
