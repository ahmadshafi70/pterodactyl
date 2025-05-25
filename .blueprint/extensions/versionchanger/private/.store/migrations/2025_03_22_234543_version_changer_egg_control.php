<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class VersionChangerEggControl extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('minecraft_version_changer_eggs', function (Blueprint $table) {
            $table->id();

            $table->json('types');
            $table->integer('egg_id')->unsigned()->nullable();
            $table->foreign('egg_id')->references('id')->on('eggs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('minecraft_version_changer_eggs');
    }
}
