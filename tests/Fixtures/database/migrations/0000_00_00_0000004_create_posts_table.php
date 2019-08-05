<?php


use Redsnapper\LaravelVersionControl\Database\Blueprint;
use Redsnapper\LaravelVersionControl\Database\Migration;

class CreatePostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tables = $this->makeVcTables("post");

        foreach($tables as $table) {
            $this->schema->table($table, function(Blueprint $table) {
                $table->uuid('user_uid')->default(''); // Cant add empty not null columns in sqlite
                $table->string('title')->default(''); // Cant add empty not null columns in sqlite
                $table->string('content')->default(''); // Cant add empty not null columns in sqlite
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
        $this->dropVcTables("post");
    }
}
