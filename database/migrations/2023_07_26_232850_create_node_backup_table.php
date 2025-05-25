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
        Schema::create('node_backups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('node_backup_group_id');
            $table->char('uuid', 36);
            $table->string('name');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique('uuid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('node_backups');
    }
};
