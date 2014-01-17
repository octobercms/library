<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DbUsersGroups extends Migration
{

    public function up()
    {
        Schema::create('users_groups', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('user_id')->unsigned();
            $table->integer('group_id')->unsigned();
            $table->primary(array('user_id', 'group_id'));
        });
    }

    public function down()
    {
        Schema::drop('users_groups');
    }

}
