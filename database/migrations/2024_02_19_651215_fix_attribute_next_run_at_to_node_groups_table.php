<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
# import DB for raw query
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        # Change the column next_run_at from the table node_backup_groups to not use the ON UPDATE CURRENT_TIMESTAMP attribute
        DB::statement('ALTER TABLE `node_backup_groups` CHANGE `next_run_at` `next_run_at` TIMESTAMP NULL DEFAULT NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('node_backup_groups', function (Blueprint $table) {
            $table->timestamp('next_run_at')->nullable();
        });
    }
};
