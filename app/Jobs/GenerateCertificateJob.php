<?php

namespace App\Jobs;

use App\Jobs\GenerateZipJob;
use App\Certificate; // Add individual certificate model
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
use App\CertificateBatch;


class GenerateCertificateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $templateJson;
    protected $participantData;
    protected $recipientName;
    protected $outputDir;
    protected $counter;
    protected $batchId;
    protected $eventName;
    protected $canvasImagePath;
    protected $signaturesPaths;


    /**
     * Create a new job instance.
     */
    public function __construct(array $data)
    {
        $this->templateJson     = $data['templateJson'];
        $this->participantData  = $data['participantData'];
        $this->recipientName    = $data['recipientName'];
        $this->outputDir        = $data['outputDir'];
        $this->counter          = $data['counter'];
        $this->batchId          = $data['batchId'];
        $this->eventName        = $data['eventName'];
        $this->canvasImagePath  = $data['canvasImagePath'] ?? null;
        $this->signaturesPaths  = $data['signaturesPaths'] ?? [];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // 1. Ubah JSON template dan data peserta menjadi array PHP
            $templateArray = json_decode($this->templateJson, true);
            $participantArray = $this->participantData;

            // 1. Hapus properti 'background' yang lama untuk menghindari konflik
            unset($templateArray['background']);

            $html = view('certificates.renderer', [
                'templateJson'    => $templateArray,
                'participantData' => $participantArray,
            ])->render();

            $safeName = Str::slug($this->recipientName);
            $pdfPath = $this->outputDir . '/' . $this->counter . '_' . $safeName . '.pdf';

            File::ensureDirectoryExists($this->outputDir);

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
                ->windowSize(1600, 1200) // Higher resolution window
                ->savePdf($pdfPath);

            Log::info("✅ Sertifikat berhasil dibuat: " . basename($pdfPath));
            Log::info('Participant data', $this->participantData);
            //Log::info('Template JSON', $templateArray);

            if (!file_exists($pdfPath)) {
                Log::warning("❌ PDF tidak ditemukan setelah proses render: $pdfPath");
                return;
            }

            // 🆕 RECORD INDIVIDUAL CERTIFICATE TO DATABASE
            $this->saveIndividualCertificate($pdfPath, $participantArray);

            // Simpan HTML untuk debug manual
            //file_put_contents(storage_path('app/test_render_debug.html'), $html);
            // Decrement job count dan cek apakah semua sudah selesai
            $remainingKey = "bulk_jobs_{$this->batchId}_remaining";
            $remaining = Cache::decrement($remainingKey);
    
            Log::info("Sisa job batch {$this->batchId}: {$remaining}");
            CertificateBatch::where('batch_id', $this->batchId)->increment('completed_jobs');
    
            if ($remaining === 0) {
                Log::info("🎉 Semua sertifikat selesai. Memulai ZIP untuk batch {$this->batchId}");
                dispatch(new GenerateZipJob($this->batchId, $this->outputDir, $this->eventName));
            }

        } catch (Throwable $e) {
            Log::error('Job Error (GenerateCertificateJob): ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            Log::error('Job Gagal Total (GenerateCertificateJob): ' . $e->getMessage());
            // Lempar kembali error agar job ini ditandai sebagai 'failed' oleh Laravel
            throw $e;
        }

    }


    protected function getCanvasImageBase64()
    {
        if (!$this->canvasImagePath || !file_exists($this->canvasImagePath)) {
            return null;
        }

        $imageContents = file_get_contents($this->canvasImagePath);
        return 'data:image/png;base64,' . base64_encode($imageContents);
    }

    /**
     * Save individual certificate record to database
     */
    protected function saveIndividualCertificate($pdfPath, $participantArray)
    {
        try {
            // Generate certificate number if not exists
            $certificateNumber = $participantArray['certificate_number'] ?? 
                                'CERT-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);

            // Extract event date from participant data or use current date
            $eventDate = null;
            if (isset($participantArray['event_date'])) {
                $eventDate = $participantArray['event_date'];
            } elseif (isset($participantArray['tanggal_acara'])) {
                $eventDate = $participantArray['tanggal_acara'];
            } else {
                $eventDate = now()->format('Y-m-d');
            }

            // Create individual certificate record
            $certificate = Certificate::create([
                'recipient_name' => $this->recipientName,
                'event_name' => $this->eventName,
                'event_date' => $eventDate,
                'certificate_number' => $certificateNumber,
                'batch_id' => $this->batchId,
                'pdf_path' => $pdfPath,
                'participant_data' => json_encode($participantArray),
                'template_data' => $this->templateJson,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info("📝 Individual certificate saved to database: ID {$certificate->id} for {$this->recipientName}");

        } catch (Throwable $e) {
            Log::error("❌ Failed to save individual certificate: " . $e->getMessage());
            // Don't throw error here - we still want the batch to continue
        }
    }

}
