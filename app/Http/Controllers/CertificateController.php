<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Certificate;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class CertificateController extends Controller
{
    /**
     * Menyiapkan data dan memanggil generator PDF.
     */
    private function generatePdf(Certificate $certificate)
    {
        // Logika pemformatan tanggal untuk satu sertifikat
        // Karena kita hanya menyimpan satu tanggal, kita format langsung
        $formattedDate = Carbon::parse($certificate->event_date)->isoFormat('D MMMM Y');

        $data = [
            'recipientName'     => $certificate->recipient_name,
            'eventName'         => $certificate->event_name,
            'formattedDate'     => $formattedDate,
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
        $pdf = $this->generatePdf($certificate);
        return $pdf->stream($certificate->recipient_name . '.pdf');
    }

    /**
     * Mengunduh PDF sertifikat.
     */
    public function download(Certificate $certificate)
    {
        $pdf = $this->generatePdf($certificate);
        return $pdf->download($certificate->recipient_name . '.pdf');
    }
}