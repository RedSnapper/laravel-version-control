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
        $tables = $this->makeVcTables("role");
        foreach($tables as $table) {
            $this->schema->table($table, function(Blueprint $table) {
                $table->string('name')->default(''); // Cant add empty not null columns in sqlite
            });
        }
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
