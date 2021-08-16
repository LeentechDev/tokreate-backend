<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNotificationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notification', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('notification_message');
            $table->integer('notification_to')->nullable()->comment("null if to all"); 
            $table->integer('notification_from')->nullable()->comment("null if to admin");
            $table->string('notification_read_by')->nullable();
            $table->string('notification_source')->nullable();
            $table->integer('notification_type')->nullable();
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
        Schema::dropIfExists('notification');
    }
}
