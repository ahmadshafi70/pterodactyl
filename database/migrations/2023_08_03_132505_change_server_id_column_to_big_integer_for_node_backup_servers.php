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
        Schema::table('node_backup_servers', function (Blueprint $table) {
            $table->unsignedBigInteger('server_id')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('node_backup_servers', function (Blueprint $table) {
            $table->unsignedInteger('server_id')->change();
        });
    }
};
