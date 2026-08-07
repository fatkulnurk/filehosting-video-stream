<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('download_servers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->string('driver', 30);
            $table->json('config');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('is_active');
        });

        Schema::create('torrents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('info_hash', 64)->unique();
            $table->string('name');
            $table->unsignedBigInteger('total_size')->default(0);
            $table->timestamps();
        });

        Schema::create('torrent_files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('torrent_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('file_checksum_id')->nullable()->constrained('file_checksums')->nullOnDelete();
            $table->string('relative_path');
            $table->string('file_name');
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();
            $table->index('torrent_id');
        });

        Schema::create('download_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->text('source');
            $table->foreignUuid('directory_id')->nullable()->constrained('directories')->nullOnDelete();
            $table->string('disk')->nullable();
            $table->string('status', 20)->default('queued');
            $table->float('progress', 8, 2)->default(0);
            $table->string('info_hash', 64)->nullable();
            $table->foreignUuid('torrent_id')->nullable()->constrained('torrents')->nullOnDelete();
            $table->foreignUuid('file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->text('error')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('download_tasks');
        Schema::dropIfExists('torrent_files');
        Schema::dropIfExists('torrents');
        Schema::dropIfExists('download_servers');
    }
};
