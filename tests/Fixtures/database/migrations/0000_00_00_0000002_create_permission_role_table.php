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
        $this->makeVcPivotTables("permission_role", "permission_uid", "role_uid");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->dropVcPivotTables("permission_role");
    }
}
