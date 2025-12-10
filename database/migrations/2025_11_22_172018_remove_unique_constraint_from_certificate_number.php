<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveUniqueConstraintFromCertificateNumber extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('certificates', function (Blueprint $table) {
            // Drop unique constraint from certificate_number
            // This allows same certificate numbers across different projects
            $table->dropUnique(['certificate_number']);
            
            // Add regular index for performance
            $table->index('certificate_number');
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
            // Restore unique constraint
            $table->dropIndex(['certificate_number']);
            $table->unique('certificate_number');
        });
    }
}
