<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddZipPathToCertificateBatchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('certificate_batches', function (Blueprint $table) {
            $table->string('zip_path')->nullable()->after('is_zipped');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('certificate_batches', function (Blueprint $table) {
            $table->dropColumn('zip_path');
        });
    }
}
