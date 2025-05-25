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
            $table->tinyInteger('max_being_made_backups')->default(1)->after('retention_days');
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
            $table->dropColumn('max_being_made_backups');
        });
    }
};
