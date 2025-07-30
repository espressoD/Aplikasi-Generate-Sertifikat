<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBatchFieldsToCertificatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->string('batch_id')->nullable()->after('certificate_number');
            $table->string('pdf_path')->nullable()->after('batch_id');
            $table->longText('participant_data')->nullable()->after('pdf_path');
            $table->longText('template_data')->nullable()->after('participant_data');
            // Remove the change() method to avoid doctrine/dbal issues
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn(['batch_id', 'pdf_path', 'participant_data', 'template_data']);
        });
    }
}
