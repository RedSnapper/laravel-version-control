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
        $this->makeVcTables("post",function(Blueprint $table){
            $table->uuid('user_uid');
            $table->string('title');
            $table->string('content');
        });
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
