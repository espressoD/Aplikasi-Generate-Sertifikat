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

    public $timeout = 600; // 🔧 Increased to 10 minutes for multi-page PDFs
    public $tries = 3; // Retry 3 times if failed
    public $maxExceptions = 3; // Max exceptions before marking as failed

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

            // 🆕 MULTI-PAGE: Check if certificate has multiple pages
            $hasMultiplePages = !empty($certificate->canvas_pages);
            
            if ($hasMultiplePages) {
                Log::info("Certificate {$this->certificateId} has multiple pages, generating multi-page PDF");
                $this->generateMultiPagePDF($certificate);
            } else {
                // Legacy single-page PDF generation
                Log::info("Certificate {$this->certificateId} is single-page, using legacy flow");
                $this->generateSinglePagePDF($certificate);
            }
            
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
     * 🆕 MULTI-PAGE: Generate multi-page PDF from canvas_pages
     */
    private function generateMultiPagePDF($certificate)
    {
        // 🔧 No json_decode needed - canvas_pages already decoded by $casts
        $canvasPages = $certificate->canvas_pages;
        
        if (!$canvasPages || !is_array($canvasPages)) {
            Log::error("Invalid canvas_pages data for certificate {$this->certificateId}");
            throw new \Exception("Invalid canvas_pages for certificate {$this->certificateId}");
        }
        
        Log::info("Generating " . count($canvasPages) . " pages for certificate {$this->certificateId}");
        
        // Generate individual PDFs for each page
        $tempPdfPaths = [];
        $participantData = json_decode($certificate->participant_data, true) ?? [];
        
        foreach ($canvasPages as $index => $pageData) {
            $pageState = $pageData['state'];
            
            if (!$pageState) {
                Log::warning("Page {$index} has no state, skipping");
                continue;
            }
            
            // Generate HTML from page state
            $html = view('certificates.renderer', [
                'templateJson'    => $pageState,
                'participantData' => $participantData,
            ])->render();
            
            // Generate temporary PDF for this page
            $tempPdfPath = sys_get_temp_dir() . '/cert_' . $certificate->id . '_page_' . ($index + 1) . '_' . uniqid() . '.pdf';
            
            Browsershot::html($html)
                ->noSandbox()
                ->timeout(120)
                ->margins(0, 0, 0, 0, 'mm')
                ->format('A4')
                ->landscape()
                ->waitUntilNetworkIdle()
                ->waitForFunction('window.__done__ === true')
                ->setOption('dumpio', true)
                ->deviceScaleFactor(2)
                ->windowSize(1123, 794)
                ->savePdf($tempPdfPath);
            
            if (!file_exists($tempPdfPath)) {
                throw new \Exception("Failed to generate PDF for page " . ($index + 1));
            }
            
            $tempPdfPaths[] = $tempPdfPath;
            Log::info("Generated PDF for page " . ($index + 1) . ": " . basename($tempPdfPath));
        }
        
        // Merge all PDFs into one
        $finalPdfPath = $this->mergePDFs($tempPdfPaths, $certificate);
        
        // Clean up temporary PDFs
        foreach ($tempPdfPaths as $tempPath) {
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }
        
        // Update certificate record
        \DB::reconnect();
        $certificate->update([
            'pdf_path' => $finalPdfPath,
            'pdf_generated_at' => now(),
        ]);
        
        Log::info("✅ Multi-page PDF generated successfully: " . basename($finalPdfPath));
        
        // 🔧 MEMORY CLEANUP: Clear memory after multi-page PDF generation
        $this->cleanupMemory();
    }
    
    /**
     * 🆕 MULTI-PAGE: Merge multiple PDFs into one using FPDI
     */
    private function mergePDFs($pdfPaths, $certificate)
    {
        // Generate final PDF path with format: {page_order}_{name}.pdf
        $safeName = strtolower(str_replace([' ', '.'], ['_', ''], $certificate->recipient_name)); // Lowercase, no dots
        $projectId = $certificate->project_id;
        $pageOrder = $certificate->page_order ?? $certificate->id; // Use page_order as counter
        $outputDir = storage_path("app/public/certificates/project_{$projectId}");
        
        File::ensureDirectoryExists($outputDir);
        
        // 🔧 NEW FORMAT: {counter}_{name}.pdf (e.g., 1_john_doe.pdf)
        $finalPdfPath = $outputDir . '/' . $pageOrder . '_' . $safeName . '.pdf';
        
        // 🔧 Suppress deprecation warnings from FPDF (get_magic_quotes_runtime deprecated in PHP 7.4+)
        $oldErrorReporting = error_reporting();
        error_reporting($oldErrorReporting & ~E_DEPRECATED);
        
        try {
            // Use FPDI to merge PDFs
            $pdf = new \setasign\Fpdi\Fpdi();
            
            foreach ($pdfPaths as $filePath) {
                $pageCount = $pdf->setSourceFile($filePath);
                
                for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                    $templateId = $pdf->importPage($pageNo);
                    $size = $pdf->getTemplateSize($templateId);
                    
                    // Add page with same orientation as source
                    $orientation = $size['width'] > $size['height'] ? 'L' : 'P';
                    $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                    $pdf->useTemplate($templateId);
                }
            }
            
            $pdf->Output('F', $finalPdfPath);
            
            Log::info("Merged " . count($pdfPaths) . " PDFs into: " . basename($finalPdfPath));
            
            // 🔧 MEMORY CLEANUP: Free FPDI object
            unset($pdf);
            
        } finally {
            // Restore original error reporting
            error_reporting($oldErrorReporting);
        }
        
        return $finalPdfPath;
    }
    
    /**
     * Legacy single-page PDF generation
     */
    private function generateSinglePagePDF($certificate)
    {
        if (!$certificate->canvas_state) {
            Log::error("Certificate {$this->certificateId} has no canvas state");
            throw new \Exception("No canvas state found for certificate {$this->certificateId}");
        }

        // 🔧 canvas_state already decoded by $casts, but keep backward compatibility check
        $canvasState = is_string($certificate->canvas_state) 
            ? json_decode($certificate->canvas_state, true) 
            : $certificate->canvas_state;

        if (!$canvasState || !is_array($canvasState)) {
            Log::error("Invalid canvas state for certificate {$this->certificateId}");
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

        // Generate PDF path with format: {page_order}_{name}.pdf
        $safeName = strtolower(str_replace([' ', '.'], ['_', ''], $certificate->recipient_name)); // Lowercase, no dots
        $projectId = $certificate->project_id;
        $pageOrder = $certificate->page_order ?? $certificate->id; // Use page_order as counter
        $outputDir = storage_path("app/public/certificates/project_{$projectId}");
        
        File::ensureDirectoryExists($outputDir);

        // 🔧 NEW FORMAT: {counter}_{name}.pdf (e.g., 1_john_doe.pdf)
        $pdfPath = $outputDir . '/' . $pageOrder . '_' . $safeName . '.pdf';

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
        
        // 🔧 MEMORY CLEANUP: Clear memory after single-page PDF generation
        $this->cleanupMemory();
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
    /**
     * 🔧 MEMORY CLEANUP: Force garbage collection to prevent memory leaks
     */
    private function cleanupMemory()
    {
        // Clear any cached data
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
        
        // Force garbage collection
        gc_collect_cycles();
        
        $memoryUsage = memory_get_usage(true) / 1024 / 1024;
        Log::info("🧹 Memory cleanup completed. Current usage: {$memoryUsage} MB");
    }}
