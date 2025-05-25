<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SubdomainManager extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('subdomains', function (Blueprint $table) {
            $table->increments('id');

            $table->json('eggs');
            $table->string('domain');
            $table->string('zone_id');
            $table->json('disallowed_subdomains_regexes');
            $table->json('api_data');
        });

        Schema::create('server_subdomains', function (Blueprint $table) {
            $table->increments('id');

            $table->string('subdomain');

            $table->integer('server_id')->unsigned()->nullable();
            $table->foreign('server_id')->references('id')->on('servers')->onDelete('set null')->nullable();
            $table->integer('subdomain_id')->unsigned();
            $table->foreign('subdomain_id')->references('id')->on('subdomains');

            $table->timestamp('created_at')->useCurrent();

            $table->unique(['subdomain', 'subdomain_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('server_subdomains');
        Schema::dropIfExists('subdomains');
    }
}
