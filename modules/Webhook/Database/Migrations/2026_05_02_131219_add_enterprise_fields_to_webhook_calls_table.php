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
        Schema::table('webhook_calls', function (Blueprint $table) {
            $table->json('request_headers')->nullable()->after('payload');
            $table->json('response_headers')->nullable()->after('status_code');
            $table->text('exception')->nullable()->after('response_body');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('webhook_calls', function (Blueprint $table) {
            $table->dropColumn(['request_headers', 'response_headers', 'exception']);
        });
    }
};
