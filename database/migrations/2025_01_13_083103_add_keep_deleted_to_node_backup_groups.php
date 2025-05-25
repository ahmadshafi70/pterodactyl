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
        Schema::table('node_backup_groups', function (Blueprint $table) {
            $table->boolean('keep_deleted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('node_backup_groups', function (Blueprint $table) {
            $table->dropColumn('keep_deleted');
        });
    }
};
