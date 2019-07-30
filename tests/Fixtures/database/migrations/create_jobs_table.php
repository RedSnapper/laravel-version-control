<?php
namespace Redsnapper\LaravelVersionControl\Tests;

use Redsnapper\LaravelVersionControl\Database\Blueprint;
use Redsnapper\LaravelVersionControl\Database\Migration;

class CreateJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tables = $this->makeVcTables("job");

        foreach($tables as $table) {
            $this->schema->table($table, function(Blueprint $table) {
                $table->uuid('user_uid')->default(''); // Cant add empty not null columns in sqlite
                $table->string('title')->default(''); // Cant add empty not null columns in sqlite
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
        $this->dropVcTables("job");
    }
}
