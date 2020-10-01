<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateThumbnailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('thumbnails', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('file_id');
            $table->foreign('file_id')
                ->references('id')
                ->on('files');
            $table->unsignedBigInteger('storage_server_id')->nullable();
            $table->foreign('storage_server_id')
                ->references('id')
                ->on('storage_servers');

            $table->text('path');
            $table->string('capture_on_percent_duration')->nullable();
            $table->string('capture_on_duration')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('thumbnails');
    }
}
