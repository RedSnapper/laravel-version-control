<?php

use Redsnapper\LaravelVersionControl\Database\Blueprint;
use Redsnapper\LaravelVersionControl\Database\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->makeVcTables("users",function(Blueprint $table){
            $table->uuid('role_uid')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
        },function(Blueprint $table){
            $table->uuid('role_uid')->nullable();
            $table->string('email');
            $table->string('password');
            $table->rememberToken();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->dropVcTables("user");
    }
}
