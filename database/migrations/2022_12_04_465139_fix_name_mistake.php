<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('automatic_phpmyadmin', function (Blueprint $table) {
            $table->renameColumn('linked_node', 'linked_database_host');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('automatic_phpmyadmin', function (Blueprint $table) {
            $table->renameColumn('linked_database_host', 'linked_node');
        });
    }
};
