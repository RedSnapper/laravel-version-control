<?php


use Redsnapper\LaravelVersionControl\Database\Blueprint;
use Redsnapper\LaravelVersionControl\Database\Migration;

class CreatePermissionRoleTable extends Migration
{
    protected $blueprint = Blueprint::class;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->makeVcTables("permission_role",function(Blueprint $table){
            $table->uuid('permission_uid');
            $table->uuid('role_uid');
            $table->string('region')->nullable();
            $table->unique(['permission_uid','role_uid']);
        },function(Blueprint $table){
            $table->uuid('permission_uid');
            $table->uuid('role_uid');
            $table->string('region')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->dropVcTables("permission_role");
    }
}
