<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BulkController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CertificateTemplateController;
use App\Http\Controllers\ProgressController;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use App\CertificateBatch;

// Homepage
Route::get('/', function () {
    return view('welcome');
});

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Sertifikat Bulk
Route::get('/generate-bulk', [BulkController::class, 'index'])->name('certificates.bulk.form');
Route::post('/generate-bulk/preview', [BulkController::class, 'preview'])->name('certificates.bulk.preview');
Route::post('/generate-bulk/download', [BulkController::class, 'storeAndDownloadZip'])->name('certificates.bulk.download');
Route::post('/certificates/render-preview', [BulkController::class, 'renderForPreview'])->name('certificates.render.preview');

// Sertifikat per Individu
Route::get('/certificate/{certificate}/show', [CertificateController::class, 'show'])->name('certificates.show');
Route::get('/certificate/{certificate}/download', [CertificateController::class, 'download'])->name('certificates.download');

// Template
Route::post('/templates', [CertificateTemplateController::class, 'store'])->name('templates.store');
Route::put('/templates/{template}', [CertificateTemplateController::class, 'update'])->name('templates.update');
Route::delete('/templates/{template}', [CertificateTemplateController::class, 'destroy'])->name('templates.destroy');

// Progress polling
//Route::get('/progress-status/{batchId}', [ProgressController::class, 'check']);

Route::get('/progress-status/{batchId}', function ($batchId) {
    $key = "bulk_jobs_{$batchId}_remaining";
    $remaining = Cache::get($key, 0);
    $total = Cache::get("bulk_jobs_{$batchId}_total", 1);
    $batch = CertificateBatch::where('batch_id', $batchId)->first();
    $zipFilename = Cache::get("bulk_jobs_{$batchId}_zip_filename");

    Log::info("Progress check for batch {$batchId}: remaining={$remaining}, total={$total}, batch=" . ($batch ? 'found' : 'not found') . ", zip_filename=" . ($zipFilename ?: 'not found'));

    $response = [
        'completed' => max($total - $remaining, 0),
        'total' => $total,
        'is_zipped' => $batch && $batch->is_zipped ? true : false,
        'event_name' => $batch ? $batch->event_name : '',
    ];

    // Add download URL if ZIP is ready
    if ($batch && $batch->is_zipped && $zipFilename) {
        $response['download_url'] = url("/download-zip/{$zipFilename}");
        $response['zip_filename'] = $zipFilename;
        Log::info("ZIP ready for download: {$response['download_url']}");
    }

    return response()->json($response);
});

// ZIP Download
Route::get('/download-zip/{filename}', function ($filename) {
    if (!Str::endsWith($filename, '.zip')) {
        abort(403, 'Format file tidak valid.');
    }

    $path = storage_path('app/' . $filename);
    if (File::exists($path)) {
        return response()->download($path);
    }

    abort(404, 'ZIP tidak ditemukan.');
});

// Diagnosis
Route::get('/cek-konfigurasi-php', function () {
    phpinfo();
});

// Test Browsershot
Route::get('/test-browsershot', function () {
    try {
        $html = '<html><body><h1 style="color:blue;">Hello World!</h1><div style="width:200px; height:200px; background:red;"></div></body></html>';

        $pdf = Browsershot::html($html)
            ->setNodeBinary('C:\\Program Files\\nodejs\\node.exe')
            ->format('A4')
            ->pdf();

        return response($pdf, 200, ['Content-Type' => 'application/pdf']);
    } catch (Exception $e) {
        return response('<h1>Tes Browsershot Gagal</h1><p>Error: ' . $e->getMessage() . '</p><pre>' . $e->getTraceAsString() . '</pre>', 500);
    }
});

// Test PDF view & generate
Route::get('/test-pdf', function () {
    return view('test-pdf');
})->name('test.view');

Route::get('/generate-test-pdf', function () {
    $html = View::make('pdf.test')->render();
    Browsershot::html($html)
        ->waitUntilNetworkIdle()
        ->format('A4')
        ->save(storage_path('app/test.pdf'));

    return response()->download(storage_path('app/test.pdf'));
});
