<?php

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class DbFiles extends Migration
{
    public function up()
    {
        Schema::create('files', function (Blueprint $table) {
            $table->increments('id');
            $table->string('disk_name');
            $table->string('file_name');
            $table->integer('file_size');
            $table->string('content_type');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('field')->nullable()->index();
            $table->integer('attachment_id')->nullable();
            $table->string('attachment_type')->nullable();
            $table->boolean('is_public')->default(true);
            $table->integer('sort_order')->nullable();
            $table->timestamps();

            $table->index(['attachment_id', 'attachment_type'], 'files_master_index');
        });
    }

    public function down()
    {
        Schema::dropIfExists('files');
    }
}
