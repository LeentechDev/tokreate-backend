<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('transaction_urgency')->nullable();
            $table->string('transaction_payment_method')->nullable()->change();
            $table->string('transaction_details')->nullable()->change();
            $table->string('transaction_service_fee')->nullable()->change();
            $table->string('transaction_allowance_fee')->nullable()->change();
            $table->string('transaction_gas_fee')->nullable()->change();
            $table->string('transaction_grand_total')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
