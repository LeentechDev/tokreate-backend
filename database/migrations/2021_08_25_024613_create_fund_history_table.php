<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFundHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fund_history', function (Blueprint $table) {
            $table->increments('fund_history_id');
            $table->integer('type')->comment("1 = sold 2 = royalties 3 = refund");
            $table->integer('amount');
            $table->integer('fund_id');
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
        Schema::dropIfExists('fund_history');
    }
}
