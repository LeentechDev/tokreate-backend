<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTablePayoutDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payout_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('user_id');
            $table->string('payout_first_name');
            $table->string('payout_middle_name');
            $table->string('payout_last_name');
            $table->string('payout_proc_id');
            $table->string('payout_proc_details');
            $table->string('payout_email_address');
            $table->string('payout_mobile_no');
            $table->date('payout_birth_date');
            $table->string('payout_street1');
            $table->string('payout_street2');
            $table->string('payout_barangay');
            $table->string('payout_city');
            $table->string('payout_province');
            $table->string('payout_country');
            $table->string('payout_currency');
            $table->string('payout_nationality');
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
        Schema::dropIfExists('payout_details');
    }
}
