<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Certificate;
use App\CertificateTemplate;
use Illuminate\Support\Facades\File;
use ZipArchive;
use Illuminate\Support\Facades\Log;
use Throwable;
use Spatie\Browsershot\Browsershot;
use Illuminate\Validation\ValidationException;
use Intervention\Image\ImageManagerStatic as Image;

class BulkController extends Controller
{
    public function index()
    {
        // Ambil semua template dari database, diurutkan berdasarkan nama
        $templates = CertificateTemplate::orderBy('name', 'asc')->get()->keyBy('id');

        // Kirim data template ke view
        return view('generate-bulk', compact('templates'));
    }

    public function renderForPreview(Request $request)
    {
        // Fungsi ini tidak membuat PDF, hanya menyiapkan data dan menampilkan view.
        $request->validate([
            'template_json' => 'required|json',
            // Hapus validasi lain karena ini hanya untuk render visual
        ]);

        $templateJson = json_decode($request->template_json, true);
        $signatureData = $this->prepareSignatureData($request);
        $participantData = $this->prepareParticipantData($request, [
            'Nama Peserta Contoh', 'email@contoh.com', 'Peran Peserta Contoh', 'ID12345', 'Divisi Contoh'
        ], $signatureData);

        // Langsung kembalikan view, jangan buat PDF
        return view('certificates.renderer', compact('templateJson', 'participantData'));
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
            $signatureData = $this->prepareSignatureData($request);
            $participants = Excel::toCollection(null, $request->file('participant_file'))[0];
            $tempPdfDir = storage_path('app/temp_certificates/' . uniqid());
            File::makeDirectory($tempPdfDir, 0755, true, true);
            
            $pdfPaths = [];
            $counter = 1;

            // Loop untuk setiap peserta
            foreach ($participants as $key => $participant) {
                if ($key == 0) continue;
                $recipientName = trim($participant[0] ?? '');
                if (empty($recipientName)) continue;

                $participantData = $this->prepareParticipantData($request, $participant, $signatureData);
                $html = view('certificates.renderer', compact('templateJson', 'participantData'))->render();
                $pdfPath = $tempPdfDir . '/' . $counter . '_' . Str::slug($recipientName) . '.pdf';
                
                // Pastikan path node juga ada di sini
                Browsershot::html($html)
                    ->setNodeBinary('C:\\Program Files\\nodejs\\node.exe') // Sesuaikan path ini jika perlu
                    ->setOption('executablePath', 'C:/Program Files/Google/Chrome/Application/chrome.exe')
                    ->waitForFunction('window.renderingComplete === true', ['timeout' => 15000])
                    ->format('A4')
                    ->landscape()
                    ->save($pdfPath);

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
     * Helper function BARU untuk memproses data tanda tangan dari form.
     */
    private function prepareSignatureData(Request $request)
    {
        $signatureData = [];
        if ($request->has('signatures')) {
            foreach ($request->signatures as $key => $signature) {
                if ($key < $request->signature_count) {
                    $image_base64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='; // Placeholder
                    
                    if ($request->hasFile("signatures.{$key}.image")) {
                        $path = $request->file("signatures.{$key}.image")->getRealPath();
                        $type = pathinfo($path, PATHINFO_EXTENSION);
                        $imgData = file_get_contents($path);
                        $image_base64 = 'data:image/' . $type . ';base64,' . base64_encode($imgData);
                    }

                    $signatureData[$key] = [
                        'name' => $signature['name'] ?? '',
                        'title' => $signature['title'] ?? '',
                        'image_base64' => $image_base64
                    ];
                }
            }
        }
        return $signatureData;
    }

    /**
     * Helper function untuk menyiapkan data yang akan dikirim ke view renderer.
     */
    private function prepareParticipantData(Request $request, $participantRow, $signatureData)
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
            'signatures'        => $signatureData,
            
            // Data yang di-generate (hanya untuk proses bulk)
            'certificateNumber' => date('Y') . '/' . date('m') . '/CERT/' . Str::random(8),
        ];
    }
}
