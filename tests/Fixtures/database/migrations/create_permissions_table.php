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

        list($versionTable, $keyTable) = $tables;

        $this->schema->table($keyTable, function(Blueprint $table) {
            $table->string('name')->unique()->default(''); // Cant add empty not null columns in sqlite
            $table->string('active')->default('on');
        });

        $this->schema->table($versionTable, function(Blueprint $table) {
            $table->string('name')->default(''); // Cant add empty not null columns in sqlite
            $table->string('active')->default('on');
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
