<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddServerImporterServerProfilesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('user_serverimporter_server_profiles', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('name', 31);
            $table->string('host', 130);
            $table->unsignedSmallInteger('port');
            $table->enum('mode', ['sftp', 'ftp']);
            $table->timestamps();

            $table->unique(['user_id', 'name']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('user_serverimporter_server_profiles');
    }
}
