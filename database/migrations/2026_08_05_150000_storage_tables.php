<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storage_servers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable();
            $table->string('name')->unique();
            $table->string('driver', 50);
            $table->json('config');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_primary')->default(false);
            $table->foreignUuid('backup_storage_server_id')->nullable()->constrained('storage_servers');
            $table->boolean('auto_backup')->default(false);
            $table->unsignedBigInteger('storage_limit')->nullable();
            $table->unsignedBigInteger('storage_used')->default(0);
            $table->softDeletes();
            $table->timestamps();
            $table->index('is_active');
            $table->index('is_primary');
        });

        Schema::create('file_checksums', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedInteger('crc32');
            $table->string('md5', 32);
            $table->string('sha1', 40);
            $table->string('sha256', 64);
            $table->string('sha512', 128);
            $table->foreignUuid('storage_server_id')->nullable()->constrained('storage_servers')->nullOnDelete();
            $table->string('disk');
            $table->string('path');
            $table->unsignedBigInteger('size');
            $table->string('mime_type');
            $table->string('file_name');
            $table->unsignedInteger('reference_count')->default(1);
            $table->timestamps();
            $table->index('crc32');
            $table->index('md5');
            $table->unique('sha256');
            $table->unique('sha1');
            $table->index(['size', 'crc32']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_checksums');
        Schema::dropIfExists('storage_servers');
    }
};