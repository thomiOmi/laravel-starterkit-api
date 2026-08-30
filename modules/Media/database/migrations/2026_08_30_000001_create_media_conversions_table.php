<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_conversions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('media_id')->constrained('media')->cascadeOnDelete();
            $table->string('name', 50);
            $table->string('disk');
            $table->string('path');
            $table->string('mime_type');
            $table->unsignedBigInteger('size')->nullable();
            $table->string('etag')->nullable();
            $table->timestamps();

            $table->unique(['media_id', 'name']);
            $table->index(['name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_conversions');
    }
};
