<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

class ServerImporterBetterTableNames extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::rename('user_serverimporter_server_profiles', 'importer_server_profiles');
        Schema::rename('user_serverimporter_credential_profiles', 'importer_credential_profiles');
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::rename('importer_server_profiles', 'user_serverimporter_server_profiles');
        Schema::rename('importer_credential_profiles', 'user_serverimporter_credential_profiles');
    }
}
