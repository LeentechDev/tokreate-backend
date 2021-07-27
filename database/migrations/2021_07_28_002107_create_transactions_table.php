<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->bigIncrements('transaction_id');
            $table->string('user_id');
            $table->string('transaction_token_id')->nullable();
            $table->integer('transaction_type');
            $table->string('transaction_payment_method');
            $table->string('transaction_details');
            $table->double('transaction_service_fee')->nullable();
            $table->double('transaction_gas_fee')->nullable();
            $table->double('transaction_allowance_fee')->nullable();
            $table->double('transaction_token_price')->nullable();
            $table->double('transaction_grand_total');
            $table->integer('transaction_status');
            $table->timestamp('transaction_created_at')->useCurrent();
            $table->timestamp('transaction_updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
