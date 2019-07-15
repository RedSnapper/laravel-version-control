<?php
namespace Redsnapper\LaravelVersionControl\Tests;

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
                $table->string('category_unique_key')->default(''); // Cant add empty not null columns in sqlite
                $table->string('name')->unique()->default(''); // Cant add empty not null columns in sqlite
                $table->boolean('hidden')->default(false);
                $table->unsignedInteger('level')->default(0);
                $table->unsignedInteger('view')->default(0);
                $table->string('comment')->default(''); // Cant add empty not null columns in sqlite
                $table->string('alphasort')->default(''); // Cant add empty not null columns in sqlite
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
        $this->dropVcTables("role");
    }
}
