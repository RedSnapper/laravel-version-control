<?php

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
        $this->makeVcTables("job",function(Blueprint $table){
            $table->uuid('user_uid');
            $table->string('title');
        });
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
