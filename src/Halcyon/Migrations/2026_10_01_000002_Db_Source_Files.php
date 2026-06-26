<?php

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::create('source_files', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('source');
            $table->string('path');
            $table->longText('content')->nullable();
            $table->string('disk')->nullable();
            $table->string('disk_path')->nullable();
            $table->unsignedInteger('file_size');
            $table->string('mime_type')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();

            $table->unique(['source', 'path']);
            $table->index('source');
            $table->index('path');
        });
    }

    public function down()
    {
        Schema::dropIfExists('source_files');
    }
};
