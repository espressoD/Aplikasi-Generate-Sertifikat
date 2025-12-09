<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCertificateProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('certificate_projects', function (Blueprint $table) {
            $table->id();
            $table->string('project_name');
            $table->string('event_name');
            $table->unsignedBigInteger('template_id')->nullable();
            $table->json('global_settings')->nullable(); // Store event details, signatures, etc.
            $table->enum('status', ['draft', 'finalizing', 'completed'])->default('draft');
            $table->integer('total_certificates')->default(0);
            $table->integer('edited_count')->default(0); // Track manually edited certificates
            $table->string('zip_path')->nullable(); // Final ZIP file path
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('certificate_projects');
    }
}
