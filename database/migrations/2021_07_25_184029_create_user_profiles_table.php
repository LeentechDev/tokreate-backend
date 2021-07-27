<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->increments('user_profile_id');
            $table->integer('user_id');
            $table->string('user_profile_full_name');
            $table->text('user_bio')->nullable();
            $table->date('user_profile_birthday')->nullable();
            $table->string('user_profile_contactno')->nullable();
            $table->string('user_profile_address')->nullable();
            $table->string('user_profile_company')->nullable();
            $table->string('user_profile_avatar')->nullable();
            $table->boolean('user_notification_settings');
            $table->timestamp('user_profile_updated_at')->useCurrent();
            $table->timestamp('user_profile_created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_profiles');
    }
}
