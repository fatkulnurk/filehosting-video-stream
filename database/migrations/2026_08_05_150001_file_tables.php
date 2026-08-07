<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->nullableUuidMorphs('model');
            $table->uuid()->nullable()->unique();
            $table->string('collection_name');
            $table->string('name');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->string('disk');
            $table->string('conversions_disk')->nullable();
            $table->unsignedBigInteger('size');
            $table->json('manipulations');
            $table->json('custom_properties');
            $table->json('generated_conversions');
            $table->json('responsive_images');
            $table->unsignedInteger('order_column')->nullable()->index();
            $table->nullableTimestamps();
        });

        Schema::create('directories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('parent_id')->nullable()->constrained('directories')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('path');
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['user_id', 'parent_id', 'slug']);
            $table->index(['user_id', 'parent_id']);
        });

        Schema::create('files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('directory_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('file_checksum_id')->nullable()->constrained('file_checksums')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('status', 20)->default('active');
            $table->unsignedBigInteger('views')->default(0);
            $table->unsignedBigInteger('downloads')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->string('password')->nullable();
            $table->unsignedInteger('download_limit')->nullable();
            $table->unsignedInteger('download_count')->default(0);
            $table->boolean('is_public')->default(true);
            $table->softDeletes();
            $table->timestamps();
            $table->index(['user_id', 'directory_id']);
            $table->index('status');
            $table->index('slug');
        });

        Schema::create('file_views', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('file_id')->constrained()->cascadeOnDelete();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('referer')->nullable();
            $table->timestamps();
            $table->index('file_id');
        });

        Schema::create('file_downloads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('file_id')->constrained()->cascadeOnDelete();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->index('file_id');
        });

        Schema::create('file_access_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('file_id')->constrained()->cascadeOnDelete();
            $table->string('action');
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
            $table->index(['file_id', 'created_at']);
        });

        Schema::create('file_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('file_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('name');
            $table->text('path');
            $table->string('disk');
            $table->unsignedBigInteger('size');
            $table->string('checksum')->nullable();
            $table->text('changelog')->nullable();
            $table->foreignUuid('uploaded_by')->constrained('users');
            $table->timestamps();
            $table->unique(['file_id', 'version']);
        });

        Schema::create('file_shares', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('file_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('shared_by')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('shared_with')->constrained('users')->cascadeOnDelete();
            $table->string('permission')->default('view');
            $table->string('password')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('download_limit')->nullable();
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamps();
            $table->unique(['file_id', 'shared_with']);
            $table->index('shared_with');
        });

        Schema::create('directory_shares', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('directory_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('shared_by')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('shared_with')->constrained('users')->cascadeOnDelete();
            $table->string('permission')->default('view');
            $table->boolean('recursive')->default(true);
            $table->timestamps();
            $table->unique(['directory_id', 'shared_with']);
            $table->index('shared_with');
        });

        Schema::create('file_favorites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('file_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'file_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_favorites');
        Schema::dropIfExists('directory_shares');
        Schema::dropIfExists('file_shares');
        Schema::dropIfExists('file_versions');
        Schema::dropIfExists('file_access_logs');
        Schema::dropIfExists('file_downloads');
        Schema::dropIfExists('file_views');
        Schema::dropIfExists('files');
        Schema::dropIfExists('directories');
        Schema::dropIfExists('media');
    }
};