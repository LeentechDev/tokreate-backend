<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTokenTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tokens', function (Blueprint $table) {
            $table->bigIncrements('token_id');
            $table->string('user_id');
            $table->integer('token_collectible');
            $table->integer('token_collectible_count');
            $table->string('token_title');
            $table->text('token_description');
            $table->double('token_starting_price');
            $table->string('token_royalty');
            $table->string('token_property')->nullable();
            $table->string('token_property_value')->nullable();
            $table->string('token_filename');
            $table->integer('token_saletype');
            $table->string('token_urgency');
            $table->integer('token_status');
            $table->timestamp('token_created_at')->useCurrent();
            $table->timestamp('token_updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tokens');
    }
}
