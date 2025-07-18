<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BulkController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CertificateTemplateController;
use Spatie\Browsershot\Browsershot;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Route untuk halaman utama Generate & Edit
Route::get('/generate-bulk', [BulkController::class, 'index'])->name('certificates.bulk.form');

// Route untuk aksi Preview dan Download
Route::post('/generate-bulk/preview', [BulkController::class, 'preview'])->name('certificates.bulk.preview');
Route::post('/generate-bulk/download', [BulkController::class, 'storeAndDownloadZip'])->name('certificates.bulk.download');
Route::post('/certificates/render-preview', 'BulkController@renderForPreview')->name('certificates.render.preview');

// Route untuk menangani sertifikat individual
Route::get('/certificate/{certificate}/show', [CertificateController::class, 'show'])->name('certificates.show');
Route::get('/certificate/{certificate}/download', [CertificateController::class, 'download'])->name('certificates.download');

// Route untuk manajemen template (Create, Update, Delete)
Route::post('/templates', [CertificateTemplateController::class, 'store'])->name('templates.store');
Route::put('/templates/{template}', [CertificateTemplateController::class, 'update'])->name('templates.update');
Route::delete('/templates/{template}', [CertificateTemplateController::class, 'destroy'])->name('templates.destroy');

// Route diagnosis (bisa dihapus jika sudah tidak diperlukan)
Route::get('/cek-konfigurasi-php', function () {
    phpinfo();
});

Route::get('/test-browsershot', function () {
    try {
        // Buat HTML yang paling sederhana
        $html = '<html><body><h1 style="color:blue;">Hello World!</h1><div style="width:200px; height:200px; background:red;"></div></body></html>';

        // Panggil Browsershot dengan konfigurasi minimal
        $pdf = Browsershot::html($html)
            ->setNodeBinary('C:\\Program Files\\nodejs\\node.exe')
            ->format('A4')
            ->pdf();

        return response($pdf, 200, ['Content-Type' => 'application/pdf']);

    } catch (Exception $e) {
        // Jika gagal, tampilkan pesan error yang detail
        return response('<h1>Tes Browsershot Gagal</h1><p>Error: ' . $e->getMessage() . '</p><pre>' . $e->getTraceAsString() . '</pre>', 500);
    }
});