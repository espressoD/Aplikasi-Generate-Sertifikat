<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProjectFieldsToCertificatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('certificates', function (Blueprint $table) {
            // Project relationship
            $table->unsignedBigInteger('project_id')->nullable()->after('id');
            $table->foreign('project_id')->references('id')->on('certificate_projects')->onDelete('cascade');
            
            // Canvas state storage
            $table->json('canvas_state')->nullable()->after('certificate_number');
            
            // Edit tracking
            $table->boolean('is_edited')->default(false)->after('canvas_state');
            $table->text('edit_history')->nullable()->after('is_edited');
            
            // Page ordering for scroll view
            $table->integer('page_order')->default(0)->after('edit_history');
            
            // PDF generation (lazy - only when finalized)
            $table->timestamp('pdf_generated_at')->nullable()->after('pdf_path');
            
            // Indexes
            $table->index('project_id');
            $table->index(['project_id', 'page_order']);
            $table->index('is_edited');
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
            $table->dropForeign(['project_id']);
            $table->dropIndex(['project_id']);
            $table->dropIndex(['project_id', 'page_order']);
            $table->dropIndex(['is_edited']);
            
            $table->dropColumn([
                'project_id',
                'canvas_state',
                'is_edited',
                'edit_history',
                'page_order',
                'pdf_generated_at'
            ]);
        });
    }
}
