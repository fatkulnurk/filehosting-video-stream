<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete()->unique();
            $table->string('phone', 20)->nullable();
            $table->string('avatar')->nullable();
            $table->text('bio')->nullable();
            $table->unsignedBigInteger('storage_limit')->nullable();
            $table->unsignedBigInteger('storage_used')->default(0);
            $table->timestamp('storage_used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
