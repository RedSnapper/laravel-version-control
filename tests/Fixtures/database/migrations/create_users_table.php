<?php
namespace Redsnapper\LaravelVersionControl\Tests;

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
        $tables = $this->makeVcTables("user");

        //TODO: Look at if this is possible instead
//        $tables = $this->makeVcTables("user", function($table) {
//
//        });

        foreach($tables as $table) {
            $this->schema->table($table, function(Blueprint $table) {
                $table->string('username')->default(''); // Cant add empty not null columns in sqlite
                $table->string('password')->default('');
                $table->string('email', 125)->default('');
                $table->string('emailp')->nullable();
                $table->string('active', 2)->default('on');
                $table->rememberToken();
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
        $this->dropVcTables("user");
    }
}
