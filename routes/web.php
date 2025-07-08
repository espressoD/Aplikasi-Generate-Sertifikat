<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BulkController;
use App\Http\Controllers\CertificateController;

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
// Route untuk menampilkan halaman form generate bulk
Route::get('/generate-bulk', [BulkController::class, 'index'])->name('certificates.bulk.form');

// Route untuk menangani preview dari form
Route::post('/generate-bulk/preview', [BulkController::class, 'preview'])->name('certificates.bulk.preview');

// Route BARU untuk memproses dan mengunduh ZIP
Route::post('/generate-bulk/download', [BulkController::class, 'storeAndDownloadZip'])->name('certificates.bulk.download');

Route::get('/certificate/{certificate}/show', [CertificateController::class, 'show'])->name('certificates.show');
Route::get('/certificate/{certificate}/download', [CertificateController::class, 'download'])->name('certificates.download');

Route::get('/cek-konfigurasi-php', function () {
    phpinfo();
});