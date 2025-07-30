<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCertificateBatchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('certificate_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_id')->unique();
            $table->string('event_name');
            $table->integer('total_jobs')->default(0);
            $table->integer('completed_jobs')->default(0);
            $table->boolean('is_zipped')->default(false);
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
        Schema::dropIfExists('certificate_batches');
    }
}
