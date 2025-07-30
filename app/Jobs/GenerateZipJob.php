<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use ZipArchive;
use Throwable;
use App\CertificateBatch;

class GenerateZipJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $batchId;
    protected $outputDir;
    protected $eventName;
    protected $recipientEmail;

    public function __construct($batchId, $outputDir, $eventName, $recipientEmail = null)
    {
        $this->batchId        = $batchId;
        $this->outputDir      = $outputDir;
        $this->eventName      = $eventName;
        $this->recipientEmail = $recipientEmail;
    }

    public function handle(): void
    {
        try {
            $zipFilename = 'sertifikat-' . Str::slug($this->eventName) . '-' . $this->batchId . '.zip';
            $zipPath = storage_path('app/' . $zipFilename);

            $zip = new ZipArchive;

            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                $files = File::files($this->outputDir);
                foreach ($files as $file) {
                    $zip->addFile($file->getRealPath(), $file->getFilename());
                }
                $zip->close();
            } else {
                throw new \Exception("Gagal membuka ZIP untuk penulisan: $zipPath");
            }

            File::deleteDirectory($this->outputDir);

            Log::info("✅ ZIP berhasil dibuat: $zipPath");
            
            // Store ZIP filename in cache for download
            Cache::put("bulk_jobs_{$this->batchId}_zip_filename", $zipFilename, now()->addHours(2));
            Log::info("📁 ZIP filename stored in cache: $zipFilename for batch: {$this->batchId}");
            
            CertificateBatch::where('batch_id', $this->batchId)->update(['is_zipped' => true]);
            Log::info("✅ CertificateBatch updated - is_zipped = true for batch: {$this->batchId}");
            @unlink(storage_path("app/canvas-{$this->batchId}.png"));
            // (Optional) Kirim notifikasi / email di sini
            // if ($this->recipientEmail) {
            //     Mail::to($this->recipientEmail)->send(new CertificateZipReadyMail($zipFilename));
            // }

        } catch (Throwable $e) {
            Log::error("❌ ZIP generation failed: " . $e->getMessage());
        }
    }
}
