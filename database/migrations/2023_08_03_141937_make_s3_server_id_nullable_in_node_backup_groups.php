<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('node_backup_groups', function (Blueprint $table) {
            $table->unsignedInteger('s3_server_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('node_backup_groups', function (Blueprint $table) {
            $table->unsignedInteger('s3_server_id')->nullable(false)->change();
        });
    }
};
