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
        Schema::create('node_backup_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('nodes_id');
            $table->string('cron_minute');
            $table->string('cron_hour');
            $table->string('cron_day_of_month');
            $table->string('cron_month');
            $table->string('cron_day_of_week');
            $table->boolean('is_active')->default(false);
            $table->timestamp('next_run_at');
            $table->timestamp('last_run_at')->nullable();
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
        Schema::dropIfExists('node_backup_groups');
    }
};
