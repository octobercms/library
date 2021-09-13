<?php

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class DbDrafts extends Migration
{
    public function up()
    {
        Schema::create('drafts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('draftable_type')->nullable();
            $table->integer('draftable_id')->unsigned()->nullable();
            $table->integer('primary_id')->unsigned()->nullable();
            $table->integer('user_id')->unsigned()->nullable()->index();
            $table->string('name')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['draftable_type', 'draftable_id'], 'drafts_master');
            $table->index(['draftable_type', 'primary_id'], 'drafts_secondary');
        });
    }

    public function down()
    {
        Schema::dropIfExists('drafts');
    }
}
