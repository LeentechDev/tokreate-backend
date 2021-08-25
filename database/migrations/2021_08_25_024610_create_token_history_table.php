<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTokenHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('token_history', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('token_id');
            $table->integer('edition_id');
            $table->integer('price');
            $table->integer('type')->comment("1 - mint 2 - buy 3 - put on sale");
            $table->integer('buyer_id')->comment("buyer");
            $table->integer('seller_id')->comment("current owner of token");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('token_history');
    }
}
