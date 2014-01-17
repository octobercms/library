<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DbSettings extends Migration 
{
    public function up()
    {
        Schema::create('settings', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('namespace');
            $table->string('group');
            $table->string('item');
            $table->text('value')->nullable();
            $table->index(['user_id', 'namespace', 'group', 'item']);
        });
    }

    public function down()
    {
        Schema::drop('settings');
    }
}
