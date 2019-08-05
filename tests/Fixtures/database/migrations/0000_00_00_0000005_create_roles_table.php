<?php


use Redsnapper\LaravelVersionControl\Database\Blueprint;
use Redsnapper\LaravelVersionControl\Database\Migration;

class CreateRolesTable extends Migration
{
    protected $blueprint = Blueprint::class;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->makeVcTables("role",function(Blueprint $table){
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
        $this->dropVcTables("role");
    }
}
