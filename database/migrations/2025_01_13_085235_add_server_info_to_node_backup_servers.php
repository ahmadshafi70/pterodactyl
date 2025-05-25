<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('node_backup_servers', function (Blueprint $table) {
            $table->bigInteger('node_id')->nullable();
            $table->string('server_uuid')->nullable();
            $table->string('server_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('node_backup_servers', function (Blueprint $table) {
            $table->dropColumn('node_id');
            $table->dropColumn('server_uuid');
            $table->dropColumn('server_name');
        });
    }
};
