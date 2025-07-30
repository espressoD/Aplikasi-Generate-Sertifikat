<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Certificate;
use Barryvdh\DomPDF\Facade\Pdf;
use Spatie\Browsershot\Browsershot;
use Carbon\Carbon;

class CertificateController extends Controller
{
    /**
     * Generate PDF using the same dynamic renderer as bulk generation
     */
    private function generatePdf(Certificate $certificate)
    {
        // Check if we have stored template and participant data (new system)
        if ($certificate->template_data && $certificate->participant_data) {
            return $this->generateDynamicPdf($certificate);
        }
        
        // Fallback to old static template for legacy certificates
        return $this->generateStaticPdf($certificate);
    }

    /**
     * Generate PDF using stored template and participant data (new system)
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
            ->windowSize(1600, 1200)
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