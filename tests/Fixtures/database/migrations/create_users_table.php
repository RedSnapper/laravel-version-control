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

        foreach($tables as $table) {
            $this->schema->table($table, function(Blueprint $table) {
                $table->uuid('role_uid')->default(''); // sqlite doesnt allow empty not null fields to be added after table creation?? Bloody weird.
                $table->string('email', 125)->default('');
                $table->string('password')->default('');
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
