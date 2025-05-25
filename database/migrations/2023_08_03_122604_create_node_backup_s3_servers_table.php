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
        Schema::create('node_backup_s3_servers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable()->default(null);
            $table->longText('default_region');
            $table->longText('access_key_id');
            $table->longText('secret_access_key');
            $table->longText('bucket');
            $table->longText('endpoint');
            $table->bigInteger('max_part_size')->default(5 * 1024 * 1024 * 1024);
            $table->bigInteger('presigned_url_lifespan')->default(60);
            $table->boolean('use_path_style_endpoint')->default(false);
            $table->boolean('use_accelerate_endpoint')->default(false);
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
        Schema::dropIfExists('node_backup_s3_servers');
    }
};
