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
        Schema::create('node_backup_servers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('node_backup_id');
            $table->unsignedInteger('server_id');
            $table->char('uuid', 36);
            $table->text('upload_id')->nullable(); // For S3 uploads, otherwise not used
            $table->string('disk');
            $table->string('checksum')->nullable();
            $table->integer('bytes')->default(0);
            $table->boolean('is_successful')->default(false);
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
        Schema::dropIfExists('node_backup_servers');
    }
};
