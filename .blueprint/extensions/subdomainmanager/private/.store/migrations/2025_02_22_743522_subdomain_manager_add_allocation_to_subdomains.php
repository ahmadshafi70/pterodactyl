<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SubdomainManagerAddAllocationToSubdomains extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('server_subdomains', function (Blueprint $table) {
            $table->integer('allocation_id')->unsigned()->nullable();
            $table->foreign('allocation_id')->references('id')->on('allocations')->onDelete('set null')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('server_subdomains', function (Blueprint $table) {
            $table->dropForeign(['allocation_id']);
            $table->dropColumn('allocation_id');
        });
    }
}
