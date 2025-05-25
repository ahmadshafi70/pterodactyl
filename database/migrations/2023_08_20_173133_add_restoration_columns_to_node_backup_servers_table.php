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
            $table->string('restoration_type')->nullable()->default(null)->after('is_successful');
            $table->unsignedInteger('restoration_node_id')->nullable()->default(null)->after('restoration_type');
            $table->timestamp('restoration_started_at')->nullable()->default(null)->after('started_at');
            $table->timestamp('restoration_completed_at')->nullable()->default(null)->after('restoration_started_at');
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
            $table->dropColumn('restoration_type');
            $table->dropColumn('restoration_node_id');
            $table->dropColumn('restoration_started_at');
            $table->dropColumn('restoration_completed_at');
        });
    }
};
