<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Certificate;
use App\CertificateTemplate;
use App\Karyawan;
use Illuminate\Support\Facades\File;
use ZipArchive;
use Illuminate\Support\Facades\Log;
use Throwable;
use Spatie\Browsershot\Browsershot;
use Illuminate\Validation\ValidationException;
use Intervention\Image\ImageManagerStatic as Image;
use App\Jobs\GenerateCertificateJob;
use App\CertificateBatch;
use App\CertificateProject;
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
        
        $karyawan = Karyawan::search($search)
            ->divisi($divisiFilter)
            ->orderBy('nama')
            ->paginate(10)
            ->appends(['search' => $search, 'divisi_filter' => $divisiFilter]);
        
        // Ambil daftar divisi untuk filter dropdown
        $divisiList = Karyawan::distinct()->pluck('divisi')->sort();

        // Kirim data ke view
        return view('generate-bulk', compact('templates', 'karyawan', 'divisiList', 'search', 'divisiFilter'));
    }

    public function renderForPreview(Request $request)
    {
        // Fungsi ini tidak membuat PDF, hanya menyiapkan data dan menampilkan view dengan sample data
        $request->validate([
            'template_json' => 'required|json',
            // Validasi optional untuk data source
        ]);

        $templateArray = json_decode($request->template_json, true);
        
        // Prepare signature data
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

        // Determine participant data based on data source
        $sampleRow = null;
        $totalParticipants = null; // Will be calculated based on data source
        $dataSource = $request->input('data_source', '');

        if ($dataSource === 'file' && $request->hasFile('participant_file')) {
            // Parse Excel/CSV and get first row
            try {
                $file = $request->file('participant_file');
                $data = Excel::toCollection(null, $file)[0];
                $totalParticipants = count($data) - 1; // Exclude header row
                
                // Skip header (row 0) and get first data row (row 1)
                if (count($data) > 1) {
                    $firstRow = $data[1]; // Index 1 = second row (first data row after header)
                    
                    $sampleRow = [
                        $firstRow[0] ?? '', // nama
                        $firstRow[1] ?? '', // email
                        $firstRow[2] ?? '', // peran
                        $firstRow[3] ?? '', // id
                        $firstRow[4] ?? '', // divisi
                        $firstRow[5] ?? '-', // nilai_1
                        $firstRow[6] ?? '-', // nilai_2
                        $firstRow[7] ?? '-', // nilai_3
                        $firstRow[8] ?? '-', // nilai_4
                    ];
                }
            } catch (\Exception $e) {
                Log::error("Error parsing file for preview: " . $e->getMessage());
                // Fall through to dummy data
            }
        } elseif ($dataSource === 'database') {
            // Get first karyawan from selection or database
            $karyawanId = null;
            
            if ($request->has('selected_karyawan') && is_array($request->selected_karyawan) && count($request->selected_karyawan) > 0) {
                $totalParticipants = count($request->selected_karyawan);
                $karyawanId = $request->selected_karyawan[0];
            }
            
            // Query karyawan
            $karyawan = $karyawanId ? Karyawan::find($karyawanId) : Karyawan::first();
            
            if ($karyawan) {
                $sampleRow = [
                    $karyawan->nama,
                    '', // email - empty for database source
                    'Peserta', // default role
                    $karyawan->npk_id,
                    $karyawan->divisi,
                    '-', // nilai_1
                    '-', // nilai_2
                    '-', // nilai_3
                    '-', // nilai_4
                ];
            }
        }

        // Fallback to dummy data if no source available
        if (!$sampleRow) {
            $totalParticipants = 1; // Dummy data = 1 participant
            $sampleRow = [
                'John Doe', 
                'johndoe@example.com', 
                'Peserta', 
                '12345', 
                'Divisi Contoh', 
                '95', 
                '90', 
                '88', 
                '92'
            ];
        }

        // Prepare participant data with certificate counter = 1 for preview
        // Pass totalParticipants for dynamic padding calculation
        $participantData = $this->prepareParticipantData($request, $sampleRow, $signatureData, 1, $totalParticipants ?? 1);

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
            
            // Log incoming request
            Log::info('storeAndDownloadZip called', [
                'data_source' => $request->data_source,
                'event_name' => $request->event_name,
                'has_file' => $request->hasFile('participant_file'),
                'has_template' => $request->has('template_json'),
            ]);

            $request->validate([
                'event_name' => 'required|string|max:255',
                'certificate_type' => 'required|string',
                'start_date' => 'required|date',
                'end_date'   => 'required|date|after_or_equal:start_date',
                'signing_date' => 'required|date',
                'signing_place' => 'required|string|max:100',
                'certificate_number_prefix' => 'required|string|max:50',
                'template_json' => 'required|json',
                'template_id' => 'required|exists:certificate_templates,id',
                'data_source' => 'required|in:file,database',
                'participant_file' => 'required_if:data_source,file|file|mimes:csv,xlsx,txt',
                'selected_karyawan' => 'required_if:data_source,database|array|min:1',
            ]);

            \DB::reconnect();

            $templateJson = json_decode($request->template_json, true);
            
            // Prepare signature data
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
                        $mimeType = $file->getMimeType();
                        $signatureData[$key]['image_base64'] = 'data:' . $mimeType . ';base64,' . base64_encode($imageContents);
                    }
                }
            }

            // Prepare participants based on data source
            $participants = [];
            if ($request->data_source === 'file') {
                try {
                    \DB::reconnect();
                    
                    $file = $request->file('participant_file');
                    
                    if ($file->getSize() > 1024 * 1024) {
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
                $selectedKaryawan = Karyawan::whereIn('id', $request->selected_karyawan)->get();
                foreach ($selectedKaryawan as $karyawan) {
                    $participants[] = [
                        $karyawan->nama,
                        '',
                        'Peserta',
                        $karyawan->npk_id,
                        $karyawan->divisi,
                        '-', '-', '-', '-',
                    ];
                }
            }

            // Calculate total participants (exclude header if file source)
            $totalParticipants = count($participants);
            if ($request->data_source === 'file') {
                $totalParticipants = max(0, $totalParticipants - 1);
            }
            
            // Create Certificate Project
            $project = CertificateProject::create([
                'project_name' => $request->event_name . ' - ' . now()->format('Y-m-d H:i'),
                'event_name' => $request->event_name,
                'template_id' => $request->template_id,
                'global_settings' => [
                    'certificate_type' => $request->certificate_type,
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    'signing_date' => $request->signing_date,
                    'signing_place' => $request->signing_place,
                    'certificate_number_prefix' => $request->certificate_number_prefix,
                    'signatures' => $signatureData,
                ],
                'total_certificates' => $totalParticipants,
                'status' => 'draft',
            ]);

            Log::info("Created project #{$project->id} with {$totalParticipants} certificates");

            // Generate canvas states for each participant
            $counter = 1;
            $createdCount = 0;
            
            // Ensure fresh database connection before bulk inserts
            \DB::disconnect();
            \DB::reconnect();
            
            foreach ($participants as $key => $participant) {
                // Skip header row for file source
                if ($request->data_source === 'file' && $key === 0) continue;

                $recipientName = trim($participant[0] ?? '');
                if (!$recipientName) continue;

                // Prepare signature data for this participant
                $participantSignatures = [];
                if (isset($request->signatures)) {
                    foreach($request->signatures as $sigKey => $sig) {
                        $participantSignatures[$sigKey] = [
                            'name' => $sig['name'] ?? '',
                            'title' => $sig['title'] ?? '',
                        ];
                        
                        if ($request->hasFile("signatures.{$sigKey}.image")) {
                            $file = $request->file("signatures.{$sigKey}.image");
                            $imageContents = file_get_contents($file->getRealPath());
                            $mimeType = $file->getMimeType();
                            $participantSignatures[$sigKey]['image_base64'] = 'data:' . $mimeType . ';base64,' . base64_encode($imageContents);
                        }
                    }
                }

                // Prepare participant data
                $participantData = $this->prepareParticipantData($request, $participant, $participantSignatures, $counter, $totalParticipants);
                $participantData['event_name'] = $request->event_name;
                
                // Generate canvas state
                $canvasState = $this->generateCanvasState($request->template_json, $participantData, $participantSignatures);
                
                if ($canvasState) {
                    // Retry mechanism for MySQL connection issues
                    $retryCount = 0;
                    $maxRetries = 3;
                    $created = false;
                    
                    while (!$created && $retryCount < $maxRetries) {
                        try {
                            // Reconnect before each insert to ensure fresh connection
                            if ($retryCount > 0) {
                                \DB::reconnect();
                                sleep(1); // Wait 1 second before retry
                            }
                            
                            // Create certificate with canvas state (NOT PDF)
                            Certificate::create([
                                'project_id' => $project->id,
                                'recipient_name' => $recipientName,
                                'event_name' => $request->event_name,
                                'event_date' => $request->start_date,
                                'certificate_number' => $participantData['certificateNumber'],
                                'canvas_state' => $canvasState,
                                'participant_data' => json_encode($participantData),
                                'template_data' => $request->template_json,
                                'page_order' => $counter,
                                'is_edited' => false,
                            ]);
                            
                            $created = true;
                            $createdCount++;
                            
                        } catch (\Illuminate\Database\QueryException $e) {
                            $retryCount++;
                            
                            if (strpos($e->getMessage(), 'MySQL server has gone away') !== false || 
                                strpos($e->getMessage(), 'Lost connection') !== false) {
                                
                                Log::warning("Database connection lost on certificate {$counter}, retrying... Attempt {$retryCount}/{$maxRetries}");
                                
                                if ($retryCount >= $maxRetries) {
                                    throw $e; // Re-throw after max retries
                                }
                            } else {
                                throw $e; // Re-throw if it's not a connection issue
                            }
                        }
                    }
                }

                $counter++;
                
                // Reconnect periodically to avoid timeout
                if ($counter % 50 === 0) {
                    \DB::reconnect();
                }
            }
            
            Log::info("Created {$createdCount} certificates for project #{$project->id}");

            // Redirect to project editor instead of starting PDF generation
            return response()->json([
                'success' => true,
                'message' => 'Project berhasil dibuat',
                'project_id' => $project->id,
                'total_certificates' => $createdCount,
                'redirect_url' => route('projects.edit', $project->id),
            ]);
            
        } catch (ValidationException $e) {
            Log::error('Validation failed', ['errors' => $e->errors()]);
            throw $e;
        } catch (Throwable $e) {
            Log::error('Error creating project: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                'debug' => config('app.debug') ? [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ] : null
            ], 500);
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
    private function prepareParticipantData(Request $request, $participantRow, $signatureData, $certificateCounter = null, $totalParticipants = null)
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
                
                // Dynamic padding based on total participants (for consistency across all certificates)
                if ($totalParticipants !== null && $totalParticipants > 0) {
                    $endNumber = $startNumber + $totalParticipants - 1;
                    $maxNumber = max($startNumber, $endNumber);
                    $padding = strlen((string)$maxNumber); // Padding based on max number
                } else {
                    // Fallback: use start number length
                    $padding = strlen($matches[1]);
                }
                
                $autoNumber = str_pad($currentNumber, $padding, '0', STR_PAD_LEFT);
                $certificateNumber = str_replace($matches[0], $autoNumber, $prefix);
            }
            // Check if prefix contains {AUTO} placeholder for flexible positioning (default start from 1)
            else if (strpos($prefix, '{AUTO}') !== false) {
                // Dynamic padding based on total participants
                if ($totalParticipants !== null && $totalParticipants > 0) {
                    $endNumber = $totalParticipants;
                    $padding = strlen((string)$endNumber); // Minimum 3 or based on total
                    $padding = max(3, $padding); // Keep minimum 3 for {AUTO} without start number
                } else {
                    $padding = 3; // Default fallback
                }
                
                $autoNumber = str_pad($certificateCounter, $padding, '0', STR_PAD_LEFT);
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
            'signingLocation'   => $fullSigningLocation, // Alias for template compatibility
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
     * Generate canvas state JSON for a single participant
     * This replaces direct PDF generation in the new project-based workflow
     */
    private function generateCanvasState($templateJson, $participantData, $signatureData = [])
    {
        $canvasData = json_decode($templateJson, true);
        
        if (!isset($canvasData['objects'])) {
            return null;
        }

        // Process each object in the template
        foreach ($canvasData['objects'] as &$object) {
            // Handle text placeholders (both textbox and i-text types)
            $isTextObject = isset($object['type']) && in_array($object['type'], ['textbox', 'i-text', 'text']);
            
            if ($isTextObject && isset($object['isPlaceholder']) && $object['isPlaceholder']) {
                $placeholderType = $object['placeholderType'] ?? '';
                
                // Replace placeholder with actual data
                // Support both @{{}} and {{}} formats
                switch ($placeholderType) {
                    case '@{{nama_penerima}}':
                    case '{{nama_penerima}}':
                        $object['text'] = $participantData['recipientName'] ?? '';
                        break;
                    case '@{{nama_acara}}':
                    case '{{nama_acara}}':
                        $object['text'] = $participantData['event_name'] ?? '';
                        break;
                    case '@{{tanggal_acara}}':
                    case '{{tanggal_acara}}':
                        $object['text'] = $participantData['eventDate'] ?? '';
                        break;
                    case '@{{nomor_sertifikat}}':
                    case '{{nomor_sertifikat}}':
                        $object['text'] = $participantData['certificateNumber'] ?? '';
                        break;
                    case '@{{jenis_sertifikat}}':
                    case '{{jenis_sertifikat}}':
                        $object['text'] = $participantData['certificateType'] ?? '';
                        break;
                    case '@{{deskripsi_acara}}':
                    case '{{deskripsi_acara}}':
                        $object['text'] = $participantData['eventDescription'] ?? '';
                        break;
                    case '@{{tempat_tanggal_ttd}}':
                    case '{{tempat_tanggal_ttd}}':
                    case '@{{tanggal_penandatanganan}}':
                    case '{{tanggal_penandatanganan}}':
                        $object['text'] = $participantData['signingLocation'] ?? '';
                        break;
                    case '@{{id_divisi}}':
                    case '{{id_divisi}}':
                    case '@{{id_lengkap_peserta}}':
                    case '{{id_lengkap_peserta}}':
                        $object['text'] = $participantData['recipientFullId'] ?? '';
                        break;
                    case '@{{peran_penerima}}':
                    case '{{peran_penerima}}':
                        $object['text'] = $participantData['recipientRole'] ?? '';
                        break;
                    // Nilai fields
                    case '@{{nilai_1}}':
                    case '{{nilai_1}}':
                        $object['text'] = $participantData['nilai1'] ?? '-';
                        break;
                    case '@{{nilai_2}}':
                    case '{{nilai_2}}':
                        $object['text'] = $participantData['nilai2'] ?? '-';
                        break;
                    case '@{{nilai_3}}':
                    case '{{nilai_3}}':
                        $object['text'] = $participantData['nilai3'] ?? '-';
                        break;
                    case '@{{nilai_4}}':
                    case '{{nilai_4}}':
                        $object['text'] = $participantData['nilai4'] ?? '-';
                        break;
                }
            }
            
            // Handle signature blocks (groups)
            if (isset($object['type']) && $object['type'] === 'group' && isset($object['isSignatureBlock']) && $object['isSignatureBlock']) {
                $sigIndex = $object['signatureIndex'] ?? 0;
                
                if (isset($signatureData[$sigIndex]) && isset($object['objects'])) {
                    foreach ($object['objects'] as &$childObj) {
                        if ($childObj['type'] === 'textbox') {
                            // Update signature name
                            if (isset($childObj['signatureField']) && $childObj['signatureField'] === 'name') {
                                $childObj['text'] = $signatureData[$sigIndex]['name'] ?? '';
                            }
                            // Update signature title
                            if (isset($childObj['signatureField']) && $childObj['signatureField'] === 'title') {
                                $childObj['text'] = $signatureData[$sigIndex]['title'] ?? '';
                            }
                        }
                        // Update signature image
                        if ($childObj['type'] === 'image' && isset($childObj['signatureField']) && $childObj['signatureField'] === 'image') {
                            if (isset($signatureData[$sigIndex]['image_base64'])) {
                                $childObj['src'] = $signatureData[$sigIndex]['image_base64'];
                            }
                        }
                    }
                }
            }
        }

        return $canvasData;
    }

    /**
     * Store new karyawan
     */
    public function storeKaryawan(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:25',
            'npk_id' => 'required|string|max:50|unique:karyawan,npk_id',
            'divisi' => 'required|string|max:100',
        ]);

        Karyawan::create($request->only(['nama', 'npk_id', 'divisi']));

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
            'npk_id' => 'required|string|max:50|unique:karyawan,npk_id,' . $id,
            'divisi' => 'required|string|max:100',
        ]);

        $karyawan->update($request->only(['nama', 'npk_id', 'divisi']));

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
        
        $karyawan = Karyawan::search($search)
            ->divisi($divisiFilter)
            ->orderBy('nama')
            ->paginate(10, ['*'], 'page', $page)
            ->appends(['search' => $search, 'divisi_filter' => $divisiFilter]);
        
        // Ambil daftar divisi untuk filter dropdown
        $divisiList = Karyawan::distinct()->pluck('divisi')->sort();

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
