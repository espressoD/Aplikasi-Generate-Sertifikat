<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMultiPageSupport extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add total_pages to certificate_templates
        Schema::table('certificate_templates', function (Blueprint $table) {
            $table->integer('total_pages')->default(1)->after('design_settings');
        });

        // Add canvas_pages to certificates
        Schema::table('certificates', function (Blueprint $table) {
            $table->longText('canvas_pages')->nullable()->after('canvas_state');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('certificate_templates', function (Blueprint $table) {
            $table->dropColumn('total_pages');
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn('canvas_pages');
        });
    }
}
