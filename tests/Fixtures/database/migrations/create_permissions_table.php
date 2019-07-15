<?php
namespace Redsnapper\LaravelVersionControl\Tests;

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
        $tables = $this->makeVcTables("permission");

        foreach($tables as $table) {
            $this->schema->table($table, function(Blueprint $table) {
                $table->string('name')->unique()->default(''); // Cant add empty not null columns in sqlite
                $table->string('active')->default('on');
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
        $this->dropVcTables("permission");
    }
}
