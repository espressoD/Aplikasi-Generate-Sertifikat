<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Certificate;
use Illuminate\Support\Facades\File;
use ZipArchive;
use Illuminate\Support\Facades\Log;
use Throwable;
use Spatie\Browsershot\Browsershot; // Import Browsershot
use Illuminate\Validation\ValidationException;

class BulkController extends Controller
{
    public function index()
    {
        return view('generate-bulk');
    }

    public function preview(Request $request)
    {
        try {
            // Validasi untuk data yang dibutuhkan preview
            $request->validate([
                'event_name' => 'required|string|max:255',
                'certificate_type' => 'required|string',
                'template_json' => 'required|json',
                'start_date' => 'required|date',
                'end_date'   => 'required|date|after_or_equal:start_date',
                'signing_date' => 'required|date',
            ]);

            $templateJson = json_decode($request->template_json, true);
            
            // Siapkan data dummy untuk preview
            $participantData = $this->prepareParticipantData($request, [
                'Nama Peserta Contoh', // recipientName
                'email@contoh.com',    // recipientEmail
                'Peran Peserta Contoh',// recipientRole
                'ID12345',             // recipientId
                'Divisi Contoh'        // recipientDivision
            ]);

            $html = view('certificates.renderer', compact('templateJson', 'participantData'))->render();

            $pdf = Browsershot::html($html)->format('A4')->landscape()->pdf();
            
            return response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="preview-sertifikat.pdf"',
            ]);

        } catch (Throwable $e) {
            Log::error('Error saat preview sertifikat: ' . $e->getMessage());
            return response('<h1>Gagal Membuat Preview</h1><p>Error: ' . $e->getMessage() . '</p><pre>' . $e->getTraceAsString() . '</pre>', 500);
        }
    }
    
    public function storeAndDownloadZip(Request $request)
    {
        try {
            set_time_limit(0);
            ini_set('memory_limit', '512M');

            // Validasi lengkap untuk proses generate
            $request->validate([
                'event_name' => 'required|string|max:255',
                'certificate_type' => 'required|string',
                'start_date' => 'required|date',
                'end_date'   => 'required|date|after_or_equal:start_date',
                'signing_date' => 'required|date',
                'template_json' => 'required|json',
                'participant_file' => 'required|file|mimes:csv,xlsx,txt',
            ]);

            $templateJson = json_decode($request->template_json, true);
            $participants = Excel::toCollection(null, $request->file('participant_file'))[0];
            $tempPdfDir = storage_path('app/temp_certificates/' . uniqid());
            File::makeDirectory($tempPdfDir, 0755, true, true);
            
            $pdfPaths = [];
            $counter = 1;

            // Loop untuk setiap peserta
            foreach ($participants as $key => $participant) {
                if ($key == 0) continue; // Lewati baris header
                $recipientName = trim($participant[0] ?? '');
                if (empty($recipientName)) continue;

                // Siapkan data dari form dan excel untuk peserta ini
                $participantData = $this->prepareParticipantData($request, $participant);

                // Render view dengan data untuk Browsershot
                $html = view('certificates.renderer', compact('templateJson', 'participantData'))->render();

                // Generate PDF menggunakan Browsershot
                $pdfPath = $tempPdfDir . '/' . $counter . '_' . Str::slug($recipientName) . '.pdf';
                
                Browsershot::html($html)->format('A4')->landscape()->save($pdfPath);

                $pdfPaths[] = $pdfPath;
                $counter++;
            }

            if (empty($pdfPaths)) {
                return back()->withErrors(['participant_file' => 'Tidak ada data peserta yang valid ditemukan di dalam file.']);
            }

            // Buat file ZIP
            $zipPath = storage_path('app/sertifikat-' . Str::slug($request->event_name) . '.zip');
            $zip = new ZipArchive;
            if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
                foreach ($pdfPaths as $path) {
                    $zip->addFile($path, basename($path));
                }
                $zip->close();
            }

            File::deleteDirectory($tempPdfDir);

            return response()->download($zipPath)->deleteFileAfterSend(true);

        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Error saat generate sertifikat bulk: ' . $e->getMessage() . ' di file ' . $e->getFile() . ' baris ' . $e->getLine());
            return back()->withErrors(['error' => 'Terjadi kesalahan server: ' . $e->getMessage()]);
        }
    }

    /**
     * Helper function untuk menyiapkan data yang akan dikirim ke view renderer.
     */
    private function prepareParticipantData(Request $request, $participantRow)
    {
        // Format tanggal acara
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $formattedEventDate = '';
        if ($startDate->isSameDay($endDate)) {
            $formattedEventDate = $startDate->isoFormat('D MMMM Y');
        } else {
            if ($startDate->month == $endDate->month && $startDate->year == $endDate->year) {
                $formattedEventDate = $startDate->format('d') . ' - ' . $endDate->isoFormat('D MMMM Y');
            } else {
                $formattedEventDate = $startDate->isoFormat('D MMMM') . ' - ' . $endDate->isoFormat('D MMMM Y');
            }
        }

        // Format tanggal penandatanganan
        $formattedSigningDate = Carbon::parse($request->signing_date)->isoFormat('D MMMM Y');

        // Gabungkan ID dan Divisi
        $recipientId = trim($participantRow[3] ?? '');
        $recipientDivision = trim($participantRow[4] ?? '');
        $recipientFullId = trim("{$recipientId} / {$recipientDivision}", ' /');

        return [
            // Data dari Excel/dummy
            'recipientName'     => trim($participantRow[0] ?? ''),
            'recipientEmail'    => trim($participantRow[1] ?? ''),
            'recipientRole'     => trim($participantRow[2] ?? ''),
            'recipientId'       => $recipientId,
            'recipientDivision' => $recipientDivision,
            'recipientFullId'   => $recipientFullId,

            // Data dari Form
            'certificateType'   => $request->certificate_type,
            'eventName'         => $request->event_name,
            'eventDate'         => $formattedEventDate,
            'signingDate'       => $formattedSigningDate,
            'description1'      => $request->descriptions[0] ?? '',
            'description2'      => $request->descriptions[1] ?? '',
            'description3'      => $request->descriptions[2] ?? '',
            
            // Data yang di-generate (hanya untuk proses bulk)
            'certificateNumber' => date('Y') . '/' . date('m') . '/CERT/' . Str::random(8),
        ];
    }
}
