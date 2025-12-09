<?php

namespace App\Jobs;

use App\Certificate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Spatie\Browsershot\Browsershot;
use Throwable;

class GeneratePDFFromCanvasJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $certificateId;

    public $timeout = 300; // 5 minutes timeout
    public $tries = 3; // Retry 3 times if failed

    /**
     * Create a new job instance.
     *
     * @param int $certificateId
     */
    public function __construct(int $certificateId)
    {
        $this->certificateId = $certificateId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Reconnect to database to avoid "MySQL server has gone away" error
            \DB::reconnect();
            
            // Load certificate with canvas state
            $certificate = Certificate::findOrFail($this->certificateId);

            if (!$certificate->canvas_state) {
                Log::error("Certificate {$this->certificateId} has no canvas state");
                throw new \Exception("No canvas state found for certificate {$this->certificateId}");
            }

            // Decode canvas state
            $canvasState = is_string($certificate->canvas_state) 
                ? json_decode($certificate->canvas_state, true) 
                : $certificate->canvas_state;

            if (!$canvasState) {
                Log::error("Invalid canvas state JSON for certificate {$this->certificateId}");
                throw new \Exception("Invalid canvas state for certificate {$this->certificateId}");
            }
            
            // Log canvas state details for debugging
            Log::info("PDF Generation for certificate {$this->certificateId}", [
                'objects_count' => count($canvasState['objects'] ?? []),
                'has_background' => isset($canvasState['backgroundImage']),
                'background_color' => $canvasState['backgroundColor'] ?? 'not set',
                'is_edited' => $certificate->is_edited
            ]);

            // Generate HTML from canvas state
            $html = view('certificates.renderer', [
                'templateJson'    => $canvasState,
                'participantData' => json_decode($certificate->participant_data, true) ?? [],
            ])->render();

            // Generate PDF path
            $safeName = Str::slug($certificate->recipient_name);
            $projectId = $certificate->project_id;
            $outputDir = storage_path("app/public/certificates/project_{$projectId}");
            
            File::ensureDirectoryExists($outputDir);

            $pdfPath = $outputDir . '/' . $certificate->id . '_' . $safeName . '.pdf';

            // Generate PDF using Browsershot
            Browsershot::html($html)
                ->noSandbox()
                ->timeout(120)
                ->margins(0, 0, 0, 0, 'mm')
                ->format('A4')
                ->landscape()
                ->waitUntilNetworkIdle()
                ->waitForFunction('window.__done__ === true')
                ->setOption('dumpio', true)
                ->deviceScaleFactor(2) // Improve resolution
                ->windowSize(1123, 794) // Match exact A4 landscape canvas size
                ->savePdf($pdfPath);

            if (!file_exists($pdfPath)) {
                Log::error("PDF not found after rendering: $pdfPath");
                throw new \Exception("Failed to generate PDF for certificate {$this->certificateId}");
            }

            Log::info("✅ PDF generated successfully: " . basename($pdfPath));

            // Update certificate record
            \DB::reconnect();
            $certificate->update([
                'pdf_path' => $pdfPath,
                'pdf_generated_at' => now(),
            ]);

            Log::info("📝 Certificate {$this->certificateId} PDF path saved to database");
            
            // Update progress in cache
            $this->updateProgress($certificate->project_id);

        } catch (Throwable $e) {
            Log::error('GeneratePDFFromCanvasJob failed for certificate ' . $this->certificateId . ': ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            // Reconnect and mark certificate as failed
            try {
                \DB::reconnect();
                Certificate::where('id', $this->certificateId)->update([
                    'pdf_path' => null,
                    'pdf_generated_at' => null,
                ]);
            } catch (\Exception $updateError) {
                Log::error('Failed to update certificate status: ' . $updateError->getMessage());
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
        Log::error("GeneratePDFFromCanvasJob permanently failed for certificate {$this->certificateId}: " . $exception->getMessage());
        
        // Mark certificate as failed permanently
        try {
            \DB::reconnect();
            Certificate::where('id', $this->certificateId)->update([
                'pdf_path' => null,
                'pdf_generated_at' => null,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to mark certificate as failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Update progress tracking in cache
     */
    private function updateProgress($projectId)
    {
        try {
            \DB::reconnect();
            
            $totalCertificates = Certificate::where('project_id', $projectId)->count();
            $completedPdfs = Certificate::where('project_id', $projectId)
                ->whereNotNull('pdf_generated_at')
                ->count();
            
            $progressKey = "project_{$projectId}_progress";
            Cache::put($progressKey, [
                'total' => $totalCertificates,
                'completed' => $completedPdfs,
                'status' => $completedPdfs < $totalCertificates ? 'processing' : 'completed',
                'current_step' => "Generating PDFs ({$completedPdfs}/{$totalCertificates})...",
            ], now()->addHours(24));
            
        } catch (\Exception $e) {
            Log::error('Failed to update progress: ' . $e->getMessage());
        }
    }
}
