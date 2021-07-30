<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateTokenTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tokens', function (Blueprint $table) {
            $table->integer('token_collectible')->comment("1 = single, 2 = multiple")->change(); 
            $table->string('token_royalty')->comment("1 = fixed price, 2 = timed auction, 3 = unlimited auction")->change();
            $table->string('token_filetype')->comment("gif,png,mp3,mp4,jpg,jpeg"); //additional
            $table->string('rarible_nft_url'); //additional
            $table->string('rarible_nft_fileurl'); //additional
            $table->integer('token_owner'); //user_id
            $table->integer('token_creator'); //user_id
            $table->integer('token_saletype')->comment("1 = fixed price, 2 = timed auction, 3 = unlimited auction")->change(); 
            $table->dropColumn(['token_urgency','token_property_value']);
            $table->renameColumn('token_property','token_properties');
            $table->renameColumn('token_filename','token_file'); //changes
            $table->integer('token_status')->comment("0 = pending, 1 = processing, 2 = failed, 3 = ready/minted/forsale, 4 = collection")->change();
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
