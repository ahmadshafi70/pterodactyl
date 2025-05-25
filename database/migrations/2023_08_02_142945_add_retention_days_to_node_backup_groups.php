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
            $table->bigInteger('retention_days')->default(-1)->after('max_server_size');
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
            $table->dropColumn('retention_days');
        });
    }
};
