<?php


use Redsnapper\LaravelVersionControl\Database\Blueprint;
use Redsnapper\LaravelVersionControl\Database\Migration;

class CreatePermissionsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->makeVcTables("permission",function(Blueprint $table){
            $table->string('name')->unique();
        },function(Blueprint $table){
            $table->string('name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->dropVcTables("permission");
    }
}
