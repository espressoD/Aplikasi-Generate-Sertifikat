<?php

namespace App\Jobs;

use App\CertificateProject;
use App\Certificate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ZipArchive;
use Throwable;

class CreateProjectZipJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $projectId;

    public $timeout = 600; // 10 minutes timeout for large projects
    public $tries = 2; // Retry 2 times if failed

    /**
     * Create a new job instance.
     *
     * @param int $projectId
     */
    public function __construct(int $projectId)
    {
        $this->projectId = $projectId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Reconnect to database to avoid "MySQL server has gone away" error
            \DB::reconnect();
            
            // Load project with certificates
            $project = CertificateProject::with('certificates')->findOrFail($this->projectId);

            // Verify all PDFs are generated
            $totalCertificates = $project->certificates->count();
            $generatedPdfs = $project->certificates->whereNotNull('pdf_path')->count();

            if ($generatedPdfs < $totalCertificates) {
                Log::warning("Project {$this->projectId}: Only {$generatedPdfs}/{$totalCertificates} PDFs generated. Waiting for completion...");
                
                // Release job back to queue to try again later
                $this->release(10); // Wait 10 seconds before retrying
                return;
            }

            Log::info("Creating ZIP for project {$this->projectId} with {$totalCertificates} certificates");

            // Generate ZIP filename

            $zipFilename = Str::slug($project->project_name) . '.zip';
            $relativeZipPath = 'public/certificates/' . $zipFilename;
            $zipPath = storage_path('app/' . $relativeZipPath);

            // Ensure directory exists
            File::ensureDirectoryExists(dirname($zipPath));

            // Create ZIP archive
            $zip = new ZipArchive;

            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \Exception("Failed to create ZIP file: $zipPath");
            }

            $addedFiles = 0;
            foreach ($project->certificates as $certificate) {
                if ($certificate->pdf_path && file_exists($certificate->pdf_path)) {
                    $fileName = basename($certificate->pdf_path);
                    $zip->addFile($certificate->pdf_path, $fileName);
                    $addedFiles++;
                } else {
                    Log::warning("PDF not found for certificate {$certificate->id}: {$certificate->pdf_path}");
                }
            }

            $zip->close();

            if ($addedFiles === 0) {
                Log::error("No PDFs added to ZIP for project {$this->projectId}");
                @unlink($zipPath);
                throw new \Exception("No PDFs found to create ZIP");
            }

            Log::info("✅ ZIP created successfully: $zipPath ({$addedFiles} files)");
            Log::info("Starting cleanup of project folder and PDF files...");
            $projectFolderName = "project_{$this->projectId}";
            $projectFolderPath = storage_path("app/public/certificates/{$projectFolderName}");
            
            if (File::exists($projectFolderPath) && File::isDirectory($projectFolderPath)) {
                try {
                    $filesInFolder = File::files($projectFolderPath);
                    $fileCount = count($filesInFolder);
                    Log::info("Found project folder: {$projectFolderPath} with {$fileCount} files");
                    File::deleteDirectory($projectFolderPath);
                    
                    Log::info("🗑️ Successfully deleted project folder: {$projectFolderName} ({$fileCount} files removed, storage space saved!)");
                } catch (\Exception $e) {
                    Log::warning("Failed to delete project folder: {$projectFolderPath}. Error: " . $e->getMessage());
                }
            } else {
                Log::warning("Project folder not found or not a directory: {$projectFolderPath}");
            }

            // Update project status
            \DB::reconnect();
            $project->update([
                'zip_path' => $relativeZipPath,
                'status' => 'completed',
                'finalized_at' => now(),
            ]);

            Log::info("📝 Project {$this->projectId} marked as completed");

        } catch (Throwable $e) {
            Log::error("CreateProjectZipJob failed for project {$this->projectId}: " . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            // Update project status to failed
            try {
                \DB::reconnect();
                CertificateProject::where('id', $this->projectId)->update([
                    'status' => 'draft', // Revert to draft so user can retry
                    'zip_path' => null,
                ]);
            } catch (\Exception $updateError) {
                Log::error('Failed to update project status: ' . $updateError->getMessage());
            }
            
            // Re-throw to mark job as failed
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error("CreateProjectZipJob permanently failed for project {$this->projectId}: " . $exception->getMessage());
        
        // Revert project to draft state
        try {
            \DB::reconnect();
            CertificateProject::where('id', $this->projectId)->update([
                'status' => 'draft',
                'zip_path' => null,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to revert project status: ' . $e->getMessage());
        }
    }
}
