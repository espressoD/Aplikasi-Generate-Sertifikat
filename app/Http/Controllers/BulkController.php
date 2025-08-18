<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Certificate;
use App\CertificateTemplate;
use App\Karyawan;
use App\Divisi;
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
    public function index(Request $request)
    {
        // Ambil semua template dari database, diurutkan berdasarkan nama
        $templates = CertificateTemplate::orderBy('name', 'asc')->get()->keyBy('id');

        // Ambil data karyawan dengan pagination dan search
        $search = $request->get('search');
        $divisiFilter = $request->get('divisi_filter');
        
        $karyawan = Karyawan::with('divisi')->search($search)
            ->divisi($divisiFilter)
            ->orderBy('nama')
            ->paginate(10)
            ->appends(['search' => $search, 'divisi_filter' => $divisiFilter]);
        
        // Ambil daftar divisi untuk filter dropdown
        $divisiList = Divisi::orderBy('inisial_unit')->pluck('inisial_unit', 'kode_divisi');

        // Kirim data ke view
        return view('generate-bulk', compact('templates', 'karyawan', 'divisiList', 'search', 'divisiFilter'));
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
            'Nama Contoh', 'email@contoh.com', 'Peserta', 'ID123', 'Divisi A', '85', '90', '78', '92'
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
                'signing_place' => 'required|string|max:100',
                'certificate_number_prefix' => 'required|string|max:50',
                'template_json' => 'required|json',
                'data_source' => 'required|in:file,database',
                'participant_file' => 'required_if:data_source,file|file|mimes:csv,xlsx,txt',
                'selected_karyawan' => 'required_if:data_source,database|array|min:1',
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

            // Prepare participants based on data source
            $participants = [];
            if ($request->data_source === 'file') {
                // Handle large files with chunking to avoid memory issues
                try {
                    // Reconnect database before processing file
                    \DB::reconnect();
                    
                    $file = $request->file('participant_file');
                    $fileExtension = $file->getClientOriginalExtension();
                    
                    // For large files, use chunked reading
                    if ($file->getSize() > 1024 * 1024) { // Files larger than 1MB
                        Log::info("Processing large file ({$file->getSize()} bytes) with chunking");
                        $participants = $this->processLargeFile($file);
                    } else {
                        Log::info("Processing small file ({$file->getSize()} bytes) normally");
                        $participants = Excel::toCollection(null, $file)[0];
                    }
                    
                    Log::info("File processed successfully. Total participants: " . count($participants));
                    
                } catch (\Exception $e) {
                    Log::error("Error processing file: " . $e->getMessage());
                    throw new \Exception("Gagal memproses file: " . $e->getMessage());
                }
            } else {
                // From database - menggunakan tabel karyawans dengan relasi divisi
                $selectedKaryawan = Karyawan::with('divisi')->whereIn('id', $request->selected_karyawan)->get();
                foreach ($selectedKaryawan as $karyawan) {
                    // Ambil inisial_unit dari relasi divisi, fallback ke kode_divisi jika tidak ada
                    $divisiDisplay = $karyawan->divisi ? $karyawan->divisi->inisial_unit : $karyawan->kode_divisi;
                    
                    $participants[] = [
                        $karyawan->nama,           // nama dari tabel karyawans
                        '', // email - empty for database source
                        'Peserta', // default role
                        $karyawan->npk,            // npk dari tabel karyawans
                        $divisiDisplay,            // inisial_unit dari tabel divisis (atau kode_divisi sebagai fallback)
                        '-', // nilai_1
                        '-', // nilai_2  
                        '-', // nilai_3
                        '-', // nilai_4
                    ];
                }
            }

            $jobCount = 0;
            $counter = 1;
            $jobsToDispatch = []; // Collect jobs for batched dispatch
            
            // Count total participants first (excluding header for file source)
            $totalParticipants = count($participants);
            if ($request->data_source === 'file') {
                $totalParticipants--; // Subtract header row
            }
            
            Log::info("Starting to process {$totalParticipants} participants");
            
            // Initialize Cache counters BEFORE starting dispatch
            Cache::put("bulk_jobs_{$batchId}_remaining", $totalParticipants, now()->addMinutes(30));
            Cache::put("bulk_jobs_{$batchId}_total", $totalParticipants, now()->addMinutes(30));
            Log::info("Cache initialized - remaining: {$totalParticipants}, total: {$totalParticipants}");
            
            // Reconnect database before processing participants
            \DB::reconnect();
            
            foreach ($participants as $key => $participant) {
                // Skip header row only for file source
                if ($request->data_source === 'file' && $key === 0) continue;

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
    
                if ($imageData && $jobCount === 0) { // Only save canvas image once
                    $image = str_replace('data:image/png;base64,', '', $imageData);
                    $image = str_replace(' ', '+', $image);
                    file_put_contents($canvasImagePath, base64_decode($image));
                }

                // Collect job data instead of dispatching immediately
                $jobsToDispatch[] = [
                    'batchId' => $batchId,
                    'templateJson' => $request->template_json,
                    'participantData' => $participantData,
                    'recipientName' => $recipientName,
                    'outputDir' => $outputDir,
                    'eventName' => $request->event_name,
                    'canvasImagePath' => $canvasImagePath,
                    'counter' => $counter++,
                    'signaturesPaths' => $signaturesPaths,
                ];

                $jobCount++;
                
                // Different dispatch strategy based on data source
                if ($request->data_source === 'file') {
                    // For file source, dispatch individually to avoid batch-related issues
                    try {
                        \DB::reconnect(); // Reconnect before each dispatch for file source
                        dispatch(new GenerateCertificateJob(end($jobsToDispatch))); // Dispatch the last added job
                        array_pop($jobsToDispatch); // Remove it from batch after successful dispatch
                        Log::info("Dispatched individual job #{$jobCount} for file source");
                    } catch (\Exception $e) {
                        Log::error("Failed to dispatch individual job #{$jobCount}: " . $e->getMessage());
                        // Continue with next job instead of failing completely
                    }
                } else {
                    // For database source, use batch dispatch (original method)
                    if (count($jobsToDispatch) >= 10) {
                        try {
                            $this->dispatchJobBatch($jobsToDispatch);
                            $jobsToDispatch = []; // Reset batch only if successful
                        } catch (\Exception $e) {
                            Log::error("Failed to dispatch batch, will retry individually: " . $e->getMessage());
                            
                            // Try to dispatch jobs individually as fallback
                            foreach ($jobsToDispatch as $singleJob) {
                                try {
                                    \DB::reconnect();
                                    dispatch(new GenerateCertificateJob($singleJob));
                                } catch (\Exception $singleError) {
                                    Log::error("Failed to dispatch individual job: " . $singleError->getMessage());
                                    // Continue with next job instead of failing completely
                                }
                            }
                            $jobsToDispatch = []; // Reset after individual dispatch attempts
                        }
                        
                        // Reconnect database and small delay
                        \DB::reconnect();
                        usleep(100000); // 0.1 second delay
                    }
                }
            }
            
            // Dispatch remaining jobs based on data source
            if (!empty($jobsToDispatch)) {
                if ($request->data_source === 'database') {
                    // Only dispatch batch for database source
                    try {
                        $this->dispatchJobBatch($jobsToDispatch);
                    } catch (\Exception $e) {
                        Log::error("Failed to dispatch final batch, trying individually: " . $e->getMessage());
                        
                        // Try to dispatch remaining jobs individually as fallback
                        foreach ($jobsToDispatch as $singleJob) {
                            try {
                                \DB::reconnect();
                                dispatch(new GenerateCertificateJob($singleJob));
                            } catch (\Exception $singleError) {
                                Log::error("Failed to dispatch final individual job: " . $singleError->getMessage());
                                // Continue with next job instead of failing completely
                            }
                        }
                    }
                } else {
                    // For file source, remaining jobs should already be dispatched individually
                    Log::info("File source: all jobs dispatched individually, no remaining batch");
                }
            }
            
            Log::info("All {$jobCount} jobs dispatched successfully for batch {$batchId}");

            // Reconnect to database before creating batch record
            \DB::reconnect();
            
            // Create CertificateBatch record in database
            $totalFromCache = Cache::get("bulk_jobs_{$batchId}_total", $jobCount);
            CertificateBatch::create([
                'batch_id' => $batchId,
                'event_name' => $request->event_name,
                'total_jobs' => $totalFromCache,
                'completed_jobs' => 0,
                'is_zipped' => false,
            ]);
            
            Log::info("CertificateBatch created: total_jobs={$totalFromCache}, dispatched_jobs={$jobCount}");
            


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

        // Format tanggal penandatanganan dengan tempat
        $signingPlace = $request->signing_place ?? '';
        $formattedSigningDate = Carbon::parse($request->signing_date)->isoFormat('D MMMM Y');
        $fullSigningLocation = $signingPlace ? $signingPlace . ', ' . $formattedSigningDate : $formattedSigningDate;

        // Gabungkan ID dan Divisi
        $recipientId = trim($participantRow[3] ?? '');
        $recipientDivision = trim($participantRow[4] ?? '');
        $recipientFullId = trim("{$recipientId} / {$recipientDivision}", ' /');

        // Generate certificate number based on user input with flexible auto-increment position and custom start number
        $certificateNumber = '';
        if ($certificateCounter !== null && $request->has('certificate_number_prefix')) {
            $prefix = $request->certificate_number_prefix;
            
            // Check if prefix contains {AUTO:start_number} placeholder for custom start number
            if (preg_match('/\{AUTO:(\d+)\}/', $prefix, $matches)) {
                $startNumber = intval($matches[1]);
                $currentNumber = $startNumber + ($certificateCounter - 1);
                
                // Determine padding based on start number length or minimum 3 digits
                $padding = max(3, strlen($matches[1]));
                $autoNumber = str_pad($currentNumber, $padding, '0', STR_PAD_LEFT);
                
                $certificateNumber = str_replace($matches[0], $autoNumber, $prefix);
            }
            // Check if prefix contains {AUTO} placeholder for flexible positioning (default start from 1)
            else if (strpos($prefix, '{AUTO}') !== false) {
                // Replace {AUTO} with auto-incremented number starting from 1
                $autoNumber = str_pad($certificateCounter, 3, '0', STR_PAD_LEFT);
                $certificateNumber = str_replace('{AUTO}', $autoNumber, $prefix);
            } 
            // Legacy support: Extract base number from prefix if it contains numbers at the end
            else if (preg_match('/^(.+?)(\d+)$/', $prefix, $matches)) {
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

        // Extract nilai columns (nilai_1, nilai_2, nilai_3, nilai_4) with default "-" if empty
        $nilai1 = trim($participantRow[5] ?? '') ?: '-';
        $nilai2 = trim($participantRow[6] ?? '') ?: '-';
        $nilai3 = trim($participantRow[7] ?? '') ?: '-';
        $nilai4 = trim($participantRow[8] ?? '') ?: '-';

        return [
            // Data dari Excel/dummy
            'recipientName'     => trim($participantRow[0] ?? ''),
            'recipientEmail'    => trim($participantRow[1] ?? ''),
            'recipientRole'     => trim($participantRow[2] ?? ''),
            'recipientId'       => $recipientId,
            'recipientDivision' => $recipientDivision,
            'recipientFullId'   => $recipientFullId,

            // Data nilai dari Excel (kolom 5-8)
            'nilai1'            => $nilai1,
            'nilai2'            => $nilai2,
            'nilai3'            => $nilai3,
            'nilai4'            => $nilai4,

            // Data dari Form
            'certificateType'   => $request->certificate_type,
            'eventName'         => $request->event_name,
            'event_name'        => $request->event_name, // Add both for compatibility
            'event_date'        => $startDate->format('Y-m-d'), // MySQL format for database
            'eventDate'         => $formattedEventDate, // Formatted for display
            'signingDate'       => $fullSigningLocation, // Combined place and date
            'description1'      => isset($request->descriptions) ? ($request->descriptions[0] ?? '') : '',
            'description2'      => isset($request->descriptions) ? ($request->descriptions[1] ?? '') : '',
            'description3'      => isset($request->descriptions) ? ($request->descriptions[2] ?? '') : '',
            'signatures'        =>  array_values($signatureData),
            
            // Generated certificate number
            'certificateNumber' => $certificateNumber,
            'certificate_number' => $certificateNumber, // Add both for compatibility
        ];
    }

    /**
     * Store new karyawan
     */
    public function storeKaryawan(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:25',
            'npk' => 'required|string|max:50|unique:karyawans,npk',
            'kode_divisi' => 'required|string|max:100',
        ]);

        Karyawan::create($request->only(['nama', 'npk', 'kode_divisi']));

        return response()->json(['success' => true, 'message' => 'Karyawan berhasil ditambahkan']);
    }

    /**
     * Update karyawan
     */
    public function updateKaryawan(Request $request, $id)
    {
        $karyawan = Karyawan::findOrFail($id);
        
        $request->validate([
            'nama' => 'required|string|max:25',
            'npk' => 'required|string|max:50|unique:karyawans,npk,' . $id,
            'kode_divisi' => 'required|string|max:100',
        ]);

        $karyawan->update($request->only(['nama', 'npk', 'kode_divisi']));

        return response()->json(['success' => true, 'message' => 'Karyawan berhasil diupdate']);
    }

    /**
     * Delete karyawan
     */
    public function deleteKaryawan($id)
    {
        $karyawan = Karyawan::findOrFail($id);
        $karyawan->delete();

        return response()->json(['success' => true, 'message' => 'Karyawan berhasil dihapus']);
    }

    /**
     * Get karyawan data via AJAX
     */
    public function getKaryawanAjax(Request $request)
    {
        $search = $request->get('search');
        $divisiFilter = $request->get('divisi_filter');
        $page = $request->get('page', 1);
        
        $karyawan = Karyawan::with('divisi')->search($search)
            ->divisi($divisiFilter)
            ->orderBy('nama')
            ->paginate(10, ['*'], 'page', $page)
            ->appends(['search' => $search, 'divisi_filter' => $divisiFilter]);
        
        // Ambil daftar divisi untuk filter dropdown
        $divisiList = Divisi::orderBy('inisial_unit')->pluck('inisial_unit', 'kode_divisi');

        return response()->json([
            'success' => true,
            'data' => $karyawan->items(),
            'pagination' => [
                'current_page' => $karyawan->currentPage(),
                'last_page' => $karyawan->lastPage(),
                'per_page' => $karyawan->perPage(),
                'total' => $karyawan->total(),
                'from' => $karyawan->firstItem(),
                'to' => $karyawan->lastItem(),
                'has_pages' => $karyawan->hasPages(),
                'prev_page' => $karyawan->previousPageUrl(),
                'next_page' => $karyawan->nextPageUrl(),
            ],
            'divisiList' => $divisiList,
            'search' => $search,
            'divisiFilter' => $divisiFilter
        ]);
    }

    /**
     * Get all karyawan IDs based on search/filter for "select all" functionality
     */
    public function getAllKaryawanIds(Request $request)
    {
        $search = $request->get('search');
        $divisiFilter = $request->get('divisi_filter');
        
        $ids = Karyawan::search($search)
            ->divisi($divisiFilter)
            ->orderBy('nama')
            ->pluck('id');

        return response()->json([
            'success' => true,
            'ids' => $ids
        ]);
    }

    /**
     * Process large Excel/CSV files with chunking to avoid memory issues
     */
    private function processLargeFile($file)
    {
        try {
            // First, try to get file info without loading it entirely
            $fileExtension = strtolower($file->getClientOriginalExtension());
            
            // Reconnect database before processing
            \DB::reconnect();
            
            if ($fileExtension === 'csv') {
                // For CSV files, use manual chunking
                return $this->processLargeCSV($file);
            } else {
                // For Excel files, try with increased memory and timeout
                Log::info("Processing Excel file with increased limits");
                
                // Increase memory limit temporarily
                $originalMemoryLimit = ini_get('memory_limit');
                ini_set('memory_limit', '1024M');
                
                // Increase timeout
                set_time_limit(300);
                
                try {
                    $participants = Excel::toCollection(null, $file)[0];
                    
                    // Restore original memory limit
                    ini_set('memory_limit', $originalMemoryLimit);
                    
                    return $participants;
                    
                } catch (\Exception $e) {
                    // Restore original memory limit
                    ini_set('memory_limit', $originalMemoryLimit);
                    throw $e;
                }
            }
            
        } catch (\Exception $e) {
            Log::error("Large file processing failed: " . $e->getMessage());
            throw new \Exception("File terlalu besar atau format tidak valid. Maksimal 1000 baris atau gunakan file CSV.");
        }
    }
    
    /**
     * Process large CSV files line by line
     */
    private function processLargeCSV($file)
    {
        $participants = collect();
        $handle = fopen($file->getRealPath(), 'r');
        
        if (!$handle) {
            throw new \Exception("Tidak dapat membuka file CSV");
        }
        
        $rowCount = 0;
        $maxRows = 1000; // Limit maximum rows
        
        try {
            while (($row = fgetcsv($handle)) !== false && $rowCount < $maxRows) {
                $participants->push($row);
                $rowCount++;
                
                // Reconnect database every 100 rows to prevent timeout
                if ($rowCount % 100 === 0) {
                    \DB::reconnect();
                    Log::info("Processed {$rowCount} rows from CSV");
                }
            }
            
            Log::info("CSV processing completed. Total rows: {$rowCount}");
            
        } finally {
            fclose($handle);
        }
        
        if ($rowCount >= $maxRows) {
            Log::warning("File truncated to {$maxRows} rows due to size limit");
        }
        
        return $participants;
    }
    
    /**
     * Dispatch jobs in batch to prevent memory overflow
     */
    private function dispatchJobBatch($jobsData)
    {
        try {
            // Reconnect to database before dispatching jobs
            \DB::reconnect();
            
            foreach ($jobsData as $jobData) {
                dispatch(new GenerateCertificateJob($jobData));
            }
            
            Log::info("Dispatched batch of " . count($jobsData) . " jobs");
            
        } catch (\Exception $e) {
            Log::error("Error dispatching job batch: " . $e->getMessage());
            Log::error("Job batch data: " . json_encode(array_keys($jobsData[0] ?? []))); // Log keys only, not full data
            
            // Don't re-throw the exception, try to continue with remaining batches
            // throw $e;
        }
    }
}
