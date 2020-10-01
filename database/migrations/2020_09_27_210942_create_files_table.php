<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')
                ->references('id')
                ->on('users');
            $table->unsignedBigInteger('directory_id')->nullable();
            $table->foreign('directory_id')
                ->references('id')
                ->on('directories');

            $table->string('code')->unique();
            $table->string('client_original_name')->nullable();
            $table->string('client_original_mime_type')->nullable();
            $table->string('client_original_extension')->nullable();
            $table->string('name')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('extension')->nullable();
            $table->text('path')->nullable();
            $table->string('size')->nullable();
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
        Schema::dropIfExists('files');
    }
}
