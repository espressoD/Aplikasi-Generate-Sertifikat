<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Certificate;
use Barryvdh\DomPDF\Facade\Pdf;
use Spatie\Browsershot\Browsershot;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class CertificateController extends Controller
{
    /**
     * Generate PDF using the same dynamic renderer as bulk generation
     */
    private function generatePdf(Certificate $certificate)
    {
        // Check if we have canvas_pages (multi-page support)
        if ($certificate->canvas_pages && is_array($certificate->canvas_pages) && count($certificate->canvas_pages) > 0) {
            return $this->generateMultiPagePdf($certificate);
        }
        
        // Check if we have stored template and participant data (new system - single page)
        if ($certificate->template_data && $certificate->participant_data) {
            return $this->generateDynamicPdf($certificate);
        }
        
        // Fallback to old static template for legacy certificates
        return $this->generateStaticPdf($certificate);
    }

    /**
     * 🆕 MULTI-PAGE: Generate multi-page PDF from canvas_pages
     */
    private function generateMultiPagePdf(Certificate $certificate)
    {
        $canvasPages = $certificate->canvas_pages;
        
        Log::info("Generating " . count($canvasPages) . " pages for certificate {$certificate->id} (individual view/download)");
        
        // Generate individual PDFs for each page
        $tempPdfPaths = [];
        $participantData = json_decode($certificate->participant_data, true) ?? [];
        
        foreach ($canvasPages as $index => $pageData) {
            $pageState = $pageData['state'] ?? null;
            
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
        
        // If only one page, return it directly
        if (count($tempPdfPaths) === 1) {
            return $tempPdfPaths[0];
        }
        
        // Merge all PDFs into one
        $finalPdfPath = $this->mergePDFs($tempPdfPaths, $certificate);
        
        // Clean up temporary PDFs
        foreach ($tempPdfPaths as $tempPath) {
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }
        
        return $finalPdfPath;
    }

    /**
     * 🆕 MULTI-PAGE: Merge multiple PDFs into one using FPDI
     */
    private function mergePDFs($pdfPaths, $certificate)
    {
        // Generate final PDF path
        $safeName = strtolower(str_replace([' ', '.'], ['_', ''], $certificate->recipient_name));
        $tempDir = sys_get_temp_dir();
        $finalPdfPath = $tempDir . '/merged_cert_' . $certificate->id . '_' . uniqid() . '.pdf';
        
        // Suppress deprecation warnings from FPDF
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
            // Restore error reporting
            error_reporting($oldErrorReporting);
        }
        
        return $finalPdfPath;
    }

    /**
     * Generate PDF using stored template and participant data (new system - single page)
     */
    private function generateDynamicPdf(Certificate $certificate)
    {
        // Decode stored data
        $templateArray = json_decode($certificate->template_data, true);
        $participantArray = json_decode($certificate->participant_data, true);

        // Remove background property to avoid conflicts
        unset($templateArray['background']);

        // Generate HTML using the same renderer as bulk generation
        $html = view('certificates.renderer', [
            'templateJson'    => $templateArray,
            'participantData' => $participantArray,
        ])->render();

        // Use Browsershot to generate PDF (same as bulk generation)
        $tempPdfPath = storage_path('app/temp_certificate_' . $certificate->id . '.pdf');
        
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
            ->windowSize(1123, 794) // Match exact A4 landscape canvas size
            ->savePdf($tempPdfPath);

        return $tempPdfPath;
    }

    /**
     * Generate PDF using old static template (legacy certificates)
     */
    private function generateStaticPdf(Certificate $certificate)
    {
        // Logika pemformatan tanggal untuk satu sertifikat
        $formattedDate = Carbon::parse($certificate->event_date)->isoFormat('D MMMM Y');

        $data = [
            'recipientName'     => $certificate->recipient_name,
            'eventName'         => $certificate->event_name,
            'eventDate'         => $formattedDate, // Fix: use eventDate instead of formattedDate
            'certificateNumber' => $certificate->certificate_number
        ];

        return Pdf::loadView('certificates.template', $data)
                    ->setPaper('a4', 'landscape');
    }

    /**
     * Menampilkan preview PDF di browser.
     */
    public function show(Certificate $certificate)
    {
        // Check if this is a new dynamic certificate or legacy static certificate
        if ($certificate->template_data && $certificate->participant_data) {
            // New system: generate PDF file and stream it
            $pdfPath = $this->generatePdf($certificate);
            
            return response()->file($pdfPath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $certificate->recipient_name . '.pdf"'
            ])->deleteFileAfterSend(true);
        } else {
            // Legacy system: use DomPDF
            $pdf = $this->generatePdf($certificate);
            return $pdf->stream($certificate->recipient_name . '.pdf');
        }
    }

    /**
     * Mengunduh PDF sertifikat.
     */
    public function download(Certificate $certificate)
    {
        // Check if this is a new dynamic certificate or legacy static certificate
        if ($certificate->template_data && $certificate->participant_data) {
            // New system: generate PDF file and download it
            $pdfPath = $this->generatePdf($certificate);
            
            return response()->download($pdfPath, $certificate->recipient_name . '.pdf')->deleteFileAfterSend(true);
        } else {
            // Legacy system: use DomPDF
            $pdf = $this->generatePdf($certificate);
            return $pdf->download($certificate->recipient_name . '.pdf');
        }
    }
}