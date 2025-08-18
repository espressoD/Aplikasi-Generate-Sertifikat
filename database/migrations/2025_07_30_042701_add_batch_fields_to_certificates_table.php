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
            // Hanya tambah kolom yang belum ada (batch_id dan pdf_path sudah ada dari migration sebelumnya)
            $table->longText('participant_data')->nullable()->after('pdf_path');
            $table->longText('template_data')->nullable()->after('participant_data');
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
            // Hanya drop kolom yang ditambahkan di migration ini
            $table->dropColumn(['participant_data', 'template_data']);
        });
    }
}
