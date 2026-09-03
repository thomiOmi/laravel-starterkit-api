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
        Schema::create('media', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->nullableUlidMorphs('model');
            $table->string('collection_name')->default('default');
            $table->string('disk');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->string('path')->unique();
            $table->string('visibility')->default('private');
            $table->string('original_name')->nullable();
            $table->string('original_extension', 20)->nullable();
            $table->string('sha256', 64)->nullable()->index();
            $table->json('meta')->nullable();
            $table->json('custom_properties')->nullable();
            $table->unsignedInteger('order_column')->default(0);
            $table->nullableUlidMorphs('uploaded_by');
            $table->timestamps();

            $table->index(['model_type', 'model_id', 'collection_name'], 'media_model_collection_index');
            $table->index(['uploaded_by_type', 'uploaded_by_id'], 'media_uploader_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
