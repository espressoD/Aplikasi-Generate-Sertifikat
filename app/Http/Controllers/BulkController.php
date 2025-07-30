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
use App\Jobs\GenerateCertificateJob;
use App\CertificateBatch;
use Illuminate\Support\Facades\Cache;


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

        $templateArray = json_decode($request->template_json, true);
        $signatureData = [];
        if ($request->has('signatures')) {
            foreach ($request->signatures as $key => $sig) {
                $signatureData[$key] = [
                    'name'  => $sig['name'] ?? '',
                    'title' => $sig['title'] ?? '',
                ];

                // Tambahkan logika untuk memproses file gambar yang diunggah
                if ($request->hasFile("signatures.{$key}.image")) {
                    $file = $request->file("signatures.{$key}.image");
                    $imageContents = file_get_contents($file->getRealPath());
                    $mimeType = $file->getMimeType();
                    $signatureData[$key]['image_base64'] = 'data:' . $mimeType . ';base64,' . base64_encode($imageContents);
                }
            }
        }

                $participantData = $this->prepareParticipantData($request, [
            'Nama Contoh', 'email@contoh.com', 'Peserta', 'ID123', 'Divisi A'
        ], $signatureData, 1); // Add certificate counter for preview

        //dd($participantData);

        // Langsung kembalikan view, jangan buat PDF
        return view('certificates.renderer', [
            'templateJson'    => $templateArray,
            'participantData' => $participantData,
        ]);
    }
    
    public function storeAndDownloadZip(Request $request)
    {
        try {
            set_time_limit(0);
            ini_set('memory_limit', '512M');

            $request->validate([
                'event_name' => 'required|string|max:255',
                'certificate_type' => 'required|string',
                'start_date' => 'required|date',
                'end_date'   => 'required|date|after_or_equal:start_date',
                'signing_date' => 'required|date',
                'certificate_number_prefix' => 'required|string|max:50',
                'template_json' => 'required|json',
                'participant_file' => 'required|file|mimes:csv,xlsx,txt',
            ]);

            $batchId = uniqid();
            $outputDir = storage_path("app/temp_certificates/{$batchId}");
            File::makeDirectory($outputDir, 0755, true, true);

            $templateJson = json_decode($request->template_json, true);
            $signatureData = $this->prepareSignatureData($request);
            $signaturesPaths = [];
            if ($request->hasFile('signatures')) {
                foreach ($request->file('signatures') as $key => $file) {
                    // Simpan file sementara dan dapatkan path-nya
                    $path = $file->store("temp_signatures/{$batchId}", 'local');
                    $signaturesPaths[$key] = storage_path('app/' . $path);
                }
            }
            $participants = Excel::toCollection(null, $request->file('participant_file'))[0];

            $jobCount = 0;
            $counter = 1;
            foreach ($participants as $key => $participant) {
                if ($key === 0) continue;

                $recipientName = trim($participant[0] ?? '');
                if (!$recipientName) continue;

                $signatureDataForJob = [];
                if (isset($request->signatures)) {
                    foreach($request->signatures as $sigKey => $sig) {
                        $signatureDataForJob[$sigKey] = [
                            'name' => $sig['name'] ?? '',
                            'title' => $sig['title'] ?? '',
                        ];
                        
                        // Add signature image processing for bulk generation
                        if ($request->hasFile("signatures.{$sigKey}.image")) {
                            $file = $request->file("signatures.{$sigKey}.image");
                            $imageContents = file_get_contents($file->getRealPath());
                            $mimeType = $file->getMimeType();
                            $signatureDataForJob[$sigKey]['image_base64'] = 'data:' . $mimeType . ';base64,' . base64_encode($imageContents);
                        }
                    }
                }

                $participantData = $this->prepareParticipantData($request, $participant, $signatureDataForJob, $counter);
                $participantData['event_name'] = $request->event_name; // pastikan ini disertakan
                
                $imageData = $request->input('canvas_image');
                $canvasImagePath = storage_path("app/canvas-{$batchId}.png");
    
                if ($imageData) {
                    $image = str_replace('data:image/png;base64,', '', $imageData);
                    $image = str_replace(' ', '+', $image);
                    file_put_contents($canvasImagePath, base64_decode($image));
                }

                dispatch(new GenerateCertificateJob([
                    'batchId' => $batchId,
                    'templateJson' => $request->template_json,
                    'participantData' => $participantData,
                    'recipientName' => $recipientName,
                    'outputDir' => $outputDir,
                    'eventName' => $request->event_name,
                    'canvasImagePath' => $canvasImagePath,
                    'counter' => $counter++,
                    'signaturesPaths' => $signaturesPaths,
                ]));

                $jobCount++;
            }

            Cache::put("bulk_jobs_{$batchId}_remaining", $jobCount, now()->addMinutes(30));
            Cache::put("bulk_jobs_{$batchId}_total", $jobCount, now()->addMinutes(30));
            
            // Create CertificateBatch record in database
            CertificateBatch::create([
                'batch_id' => $batchId,
                'event_name' => $request->event_name,
                'total_jobs' => $jobCount,
                'completed_jobs' => 0,
                'is_zipped' => false,
            ]);
            


            return response()->json([
                'message' => 'Proses dimulai',
                'batchId' => $batchId,
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Error saat dispatch queue: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }



    /**
     * Helper function BARU untuk memproses data tanda tangan dari form.
     */
    private function prepareSignatureData(Request $request)
    {
        $signatureData = [];
        if ($request->has('signatures')) {
            foreach ($request->signatures as $key => $sig) {
                $signatureData[$key] = [
                    'name'  => $sig['name'] ?? '',
                    'title' => $sig['title'] ?? '',
                ];
                if ($request->hasFile("signatures.{$key}.image")) {
                    $file = $request->file("signatures.{$key}.image");
                    $imageContents = file_get_contents($file->getRealPath());
                    $signatureData[$key]['image_base64'] = 'data:' . $file->getMimeType() . ';base64,' . base64_encode($imageContents);
                }
            }
        }
        return $signatureData;
    }


    /**
     * Helper function untuk menyiapkan data yang akan dikirim ke view renderer.
     */
    private function prepareParticipantData(Request $request, $participantRow, $signatureData, $certificateCounter = null)
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

        // Generate certificate number based on user input
        $certificateNumber = '';
        if ($certificateCounter !== null && $request->has('certificate_number_prefix')) {
            // For bulk generation: use prefix + counter
            $prefix = $request->certificate_number_prefix;
            
            // Extract base number from prefix if it contains numbers at the end
            if (preg_match('/^(.+?)(\d+)$/', $prefix, $matches)) {
                $basePrefix = $matches[1];
                $startNumber = intval($matches[2]);
                $newNumber = $startNumber + ($certificateCounter - 1);
                $certificateNumber = $basePrefix . str_pad($newNumber, strlen($matches[2]), '0', STR_PAD_LEFT);
            } else {
                // If no number pattern, just append counter
                $certificateNumber = $prefix . '-' . str_pad($certificateCounter, 3, '0', STR_PAD_LEFT);
            }
        } else {
            // Fallback for preview or old system
            $certificateNumber = date('Y') . '/' . date('m') . '/CERT/' . Str::random(8);
        }

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
            'event_name'        => $request->event_name, // Add both for compatibility
            'event_date'        => $formattedEventDate, // Add for database storage
            'eventDate'         => $formattedEventDate,
            'signingDate'       => $formattedSigningDate,
            'description1'      => isset($request->descriptions) ? ($request->descriptions[0] ?? '') : '',
            'description2'      => isset($request->descriptions) ? ($request->descriptions[1] ?? '') : '',
            'description3'      => isset($request->descriptions) ? ($request->descriptions[2] ?? '') : '',
            'signatures'        =>  array_values($signatureData),
            
            // Generated certificate number
            'certificateNumber' => $certificateNumber,
            'certificate_number' => $certificateNumber, // Add both for compatibility
        ];
    }
}
