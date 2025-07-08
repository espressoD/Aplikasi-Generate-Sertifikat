<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Certificate;
use Illuminate\Support\Facades\File;
use ZipArchive;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log; 
use Illuminate\Validation\ValidationException;

class BulkController extends Controller
{
    /**
     * Menampilkan form untuk generate sertifikat bulk.
     */
    public function index()
    {
        return view('generate-bulk');
    }

    /**
     * Menangani form untuk membuat preview PDF sertifikat.
     */
    public function preview(Request $request)
    {
        // 1. Validasi input (dibuat lebih longgar untuk preview)
        $request->validate([
            'event_name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'signature_count' => 'required|integer|min:1|max:3',
            'signatures.*.image' => 'nullable|image|mimes:png|max:1024', // Gambar opsional
        ]);

        // 2. Logika pemformatan tanggal (tidak berubah)
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $formattedDate = '';
        if ($startDate->isSameDay($endDate)) {
            $formattedDate = $startDate->isoFormat('D MMMM Y');
        } else {
            if ($startDate->month == $endDate->month && $startDate->year == $endDate->year) {
                $formattedDate = $startDate->format('d') . ' - ' . $endDate->isoFormat('D MMMM Y');
            } else if ($startDate->year == $endDate->year) {
                $formattedDate = $startDate->isoFormat('D MMMM') . ' - ' . $endDate->isoFormat('D MMMM Y');
            } else {
                $formattedDate = $startDate->isoFormat('D MMMM Y') . ' - ' . $endDate->isoFormat('D MMMM Y');
            }
        }

        // --- BAGIAN YANG DIPERBAIKI: Memproses data tanda tangan ---
        $signatureData = [];
        if ($request->has('signatures')) {
            foreach ($request->signatures as $key => $signature) {
                // Hanya proses sejumlah yang dipilih di dropdown
                if ($key < $request->signature_count) {
                    $image_path = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='; // Placeholder transparan

                    // Cek jika ada file gambar yang di-upload untuk preview
                    if ($request->hasFile("signatures.{$key}.image")) {
                        $path = $request->file("signatures.{$key}.image")->getRealPath();
                        $type = pathinfo($path, PATHINFO_EXTENSION);
                        $imgData = file_get_contents($path);
                        $image_path = 'data:image/' . $type . ';base64,' . base64_encode($imgData);
                    }

                    $signatureData[] = [
                        'name' => $signature['name'] ?? 'Nama Penandatangan',
                        'title' => $signature['title'] ?? 'Jabatan',
                        'image_path' => $image_path
                    ];
                }
            }
        }

        // 4. Siapkan data untuk dikirim ke view PDF
        $data = [
            'recipientName'     => 'Nama Peserta Contoh',
            'eventName'         => $request->event_name,
            'formattedDate'     => $formattedDate,
            'certificateNumber' => date('Y') . '/PREVIEW/' . Str::random(8),
            'signatures'        => $signatureData // Kirim data tanda tangan yang sudah diproses
        ];

        $pdf = Pdf::loadView('certificates.template', $data)
                    ->setPaper('a4', 'landscape');

        return $pdf->stream('preview-sertifikat.pdf');
    }

    
    public function storeAndDownloadZip(Request $request)
    {
        try {
            set_time_limit(0);
            ini_set('memory_limit', '256M');

            // Validasi dasar
            $request->validate([
                'event_name' => 'required|string|max:255',
                'start_date' => 'required|date',
                'end_date'   => 'required|date|after_or_equal:start_date',
                'participant_file' => 'required|file|mimes:csv,xlsx,txt',
                'signature_count' => 'required|integer|min:1|max:3',
            ]);

            // Validasi dinamis untuk tanda tangan
            $signatureRules = [];
            for ($i = 0; $i < $request->signature_count; $i++) {
                $signatureRules["signatures.{$i}.name"] = 'required|string|max:255';
                $signatureRules["signatures.{$i}.title"] = 'required|string|max:255';
                $signatureRules["signatures.{$i}.image"] = 'required|image|mimes:png|max:1024';
            }
            $request->validate($signatureRules, [
                'signatures.*.name.required' => 'Nama penandatangan #:position wajib diisi.',
                'signatures.*.title.required' => 'Jabatan penandatangan #:position wajib diisi.',
                'signatures.*.image.required' => 'Gambar tanda tangan #:position wajib diisi.',
            ]);

            // Logika pemformatan tanggal
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            $formattedDate = '';
            if ($startDate->isSameDay($endDate)) {
                $formattedDate = $startDate->isoFormat('D MMMM Y');
            } else {
                if ($startDate->month == $endDate->month && $startDate->year == $endDate->year) {
                    $formattedDate = $startDate->format('d') . ' - ' . $endDate->isoFormat('D MMMM Y');
                } else if ($startDate->year == $endDate->year) {
                    $formattedDate = $startDate->isoFormat('D MMMM') . ' - ' . $endDate->isoFormat('D MMMM Y');
                } else {
                    $formattedDate = $startDate->isoFormat('D MMMM Y') . ' - ' . $endDate->isoFormat('D MMMM Y');
                }
            }

            // Simpan file tanda tangan ke penyimpanan sementara
            $tempSignatureDir = 'temp_signatures/' . uniqid();
            $signatureData = [];
            if ($request->has('signatures')) {
                foreach ($request->signatures as $key => $signature) {
                    if ($key < $request->signature_count) {
                        $path = $request->file("signatures.{$key}.image")->store($tempSignatureDir, 'local');
                        $signatureData[] = [
                            'name' => $signature['name'],
                            'title' => $signature['title'],
                            'image_path' => storage_path('app/' . $path)
                        ];
                    }
                }
            }

            // Baca file Excel/CSV
            $participants = Excel::toCollection(null, $request->file('participant_file'))[0];
            $tempPdfDir = storage_path('app/temp_certificates/' . uniqid());
            File::makeDirectory($tempPdfDir, 0755, true, true);
            
            $pdfPaths = [];
            $counter = 1;

            // Loop untuk setiap peserta
            foreach ($participants as $key => $participant) {
                if ($key == 0 && (strtolower($participant[0]) == 'nama' || strtolower($participant[0]) == 'name')) {
                    continue;
                }
                $recipientName = $participant[0];
                if (empty(trim($recipientName))) {
                    continue;
                }

                $certificateNumber = date('Y') . '/' . date('m') . '/CERT/' . Str::random(8);
                Certificate::create([
                    'recipient_name' => $recipientName,
                    'event_name' => $request->event_name,
                    'event_date' => $request->start_date,
                    'certificate_number' => $certificateNumber
                ]);

                $data = [
                    'recipientName'     => $recipientName,
                    'eventName'         => $request->event_name,
                    'formattedDate'     => $formattedDate,
                    'certificateNumber' => $certificateNumber,
                    'signatures'        => $signatureData
                ];

                $pdfFileName = $counter . '_' . Str::slug($recipientName) . '.pdf';
                $pdfPath = $tempPdfDir . '/' . $pdfFileName;
                Pdf::loadView('certificates.template', $data)->setPaper('a4', 'landscape')->save($pdfPath);
                $pdfPaths[] = $pdfPath;
                $counter++;
            }

            if (empty($pdfPaths)) {
                if(isset($tempSignatureDir)) File::deleteDirectory(storage_path('app/' . $tempSignatureDir));
                File::deleteDirectory($tempPdfDir);
                return back()->withErrors(['participant_file' => 'Tidak ada data peserta yang valid ditemukan di dalam file.']);
            }

            // Buat file ZIP
            $zip = new ZipArchive;
            $zipFileName = 'sertifikat-' . Str::slug($request->event_name) . '.zip';
            $zipPath = storage_path('app/' . $zipFileName);

            if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
                foreach ($pdfPaths as $path) {
                    $zip->addFile($path, basename($path));
                }
                $zip->close();
            }

            // Hapus semua direktori sementara
            File::deleteDirectory($tempPdfDir);
            if(isset($tempSignatureDir)) File::deleteDirectory(storage_path('app/' . $tempSignatureDir));

            return response()->download($zipPath)->deleteFileAfterSend(true);

        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Error saat generate sertifikat bulk: ' . $e->getMessage() . ' di file ' . $e->getFile() . ' baris ' . $e->getLine());
            if (isset($tempPdfDir) && File::isDirectory($tempPdfDir)) File::deleteDirectory($tempPdfDir);
            if (isset($tempSignatureDir) && File::isDirectory(storage_path('app/' . $tempSignatureDir))) File::deleteDirectory(storage_path('app/' . $tempSignatureDir));
            return back()->withErrors(['error' => 'Terjadi kesalahan server yang tidak terduga: ' . $e->getMessage()]);
        }
    }
}
