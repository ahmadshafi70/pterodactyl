<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AddServerLimitToNodes extends Migration
{
    /**
     * The name of the table for this migration.
     *
     * @var string
     */
    protected $tableName = 'nodes';

    public function up(): void
    {
        Schema::table($this->tableName, function ($table) {
            $table->unsignedInteger('servers_limit')
                ->nullable()
                ->after('description')
                ->comment('Maximum number of servers that can be assigned to this node.');
        });
    }

    public function down(): void
    {
        Schema::table($this->tableName, function ($table) {
            $table->dropColumn('servers_limit');
        });
    }
}
