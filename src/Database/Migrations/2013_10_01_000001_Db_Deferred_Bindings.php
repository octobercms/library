<?php

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class DbDeferredBindings extends Migration
{
    public function up()
    {
        Schema::create('deferred_bindings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('master_type');
            $table->string('master_field');
            $table->string('slave_type');
            $table->integer('slave_id');
            $table->string('session_key');
            $table->boolean('is_bind')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('deferred_bindings');
    }
}
