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
                $table->string('category_uid')->default(''); // Cant add empty not null columns in sqlite
                $table->boolean('hidden')->default(false);
                $table->unsignedInteger('level')->default(0);
                $table->unsignedInteger('view')->default(0);
                $table->string('comment')->default(''); // Cant add empty not null columns in sqlite
                $table->string('alphasort')->default(''); // Cant add empty not null columns in sqlite
                $table->string('active')->default('on');
            });
        }

        list($versionTable, $keyTable) = $tables;

        $this->schema->table($keyTable, function(Blueprint $table) {
            $table->string('name')->unique()->default(''); // Cant add empty not null columns in sqlite
        });

        $this->schema->table($versionTable, function(Blueprint $table) {
            $table->string('name')->default(''); // Cant add empty not null columns in sqlite
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
