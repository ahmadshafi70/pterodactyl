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
            $table->boolean('to_be_deleted')->default(false)->after('is_active');
        });

        Schema::table('node_backups', function (Blueprint $table) {
            $table->boolean('to_be_deleted')->default(false)->after('name');
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
            $table->dropColumn('to_be_deleted');
        });

        Schema::table('node_backups', function (Blueprint $table) {
            $table->dropColumn('to_be_deleted');
        });
    }
};
