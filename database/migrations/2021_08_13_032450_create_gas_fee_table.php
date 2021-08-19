<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGasFeeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gas_fees', function (Blueprint $table) {
            $table->bigIncrements('gas_fee_id');
            $table->string('gas_fee_name');
            $table->double('gas_fee_amount');
            $table->timestamp('gas_fee_created_at')->useCurrent();
            $table->timestamp('gas_fee_updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gas_fees');
    }
}
