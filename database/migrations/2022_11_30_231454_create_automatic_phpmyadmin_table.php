<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('automatic_phpmyadmin', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('url');
            $table->string('cookie_name');
            $table->string('cookie_domain');
            $table->string('encryption_key');
            $table->string('encryption_iv');
            $table->boolean('one_click_admin_login_enabled');
            $table->integer('linked_node')->unsigned()->nullable();
            // $table->foreign('linked_node')->references('id')->on('nodes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('automatic_phpmyadmin');
    }
};
