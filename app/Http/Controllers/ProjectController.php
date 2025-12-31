<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\CertificateProject;
use App\Certificate;
use App\Jobs\GeneratePDFFromCanvasJob;
use App\Jobs\CreateProjectZipJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ProjectController extends Controller
{
    /**
     * Display a listing of all projects
     */
    public function index()
    {
        $projects = CertificateProject::with('template')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('projects.index', compact('projects'));
    }

    /**
     * Show the project editor for editing certificates
     */
    public function edit($id)
    {
        $project = CertificateProject::with(['certificates' => function($query) {
            $query->orderBy('page_order', 'asc');
        }, 'template'])->findOrFail($id);

        // Check if project is editable
        if (!$project->isEditable()) {
            return redirect()->route('projects.index')
                ->with('error', 'Project ini sudah difinalisasi dan tidak bisa diedit.');
        }

        return view('projects.editor', compact('project'));
    }

    /**
     * Update a single certificate's canvas state via AJAX
     */
    public function updateCertificate(Request $request)
    {
        try {
            DB::reconnect();

            // Handle both JSON and form-data requests
            $certificateId = $request->input('certificate_id');
            $canvasState = $request->input('canvas_state');
            $canvasPages = $request->input('canvas_pages'); // 🆕 MULTI-PAGE: Accept canvas_pages
            
            // If canvas_state is string (from JSON.stringify), decode it
            if (is_string($canvasState)) {
                $canvasState = json_decode($canvasState, true);
            }
            
            // 🆕 MULTI-PAGE: Decode canvas_pages if string
            if (is_string($canvasPages)) {
                $canvasPages = json_decode($canvasPages, true);
            }
            
            if (!$certificateId || !$canvasState) {
                return response()->json([
                    'success' => false,
                    'message' => 'Certificate ID dan canvas state required'
                ], 400);
            }

            $certificate = Certificate::findOrFail($certificateId);
            
            // Check if certificate belongs to an editable project
            if (!$certificate->isEditable()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sertifikat ini tidak bisa diedit.'
                ], 403);
            }

            // Log for debugging
            Log::info('Updating certificate', [
                'id' => $certificate->id,
                'recipient' => $certificate->recipient_name, // 🔧 ADD: Log participant name
                'objects_count' => count($canvasState['objects'] ?? []),
                'has_background' => isset($canvasState['backgroundImage']),
                'is_multipage' => $canvasPages !== null,
                'total_pages' => $canvasPages ? count($canvasPages) : 1,
                'current_canvas_pages_count' => $certificate->canvas_pages ? count($certificate->canvas_pages) : 0, // 🔧 ADD: Check existing pages
            ]);

            $certificate->saveCanvasState($canvasState);
            
            // 🆕 MULTI-PAGE: Save canvas_pages if provided (no json_encode - handled by $casts)
            if ($canvasPages !== null) {
                $certificate->canvas_pages = $canvasPages; // Laravel auto-encodes via $casts
                $certificate->save();
            }
            
            $certificate->markAsEdited($request->user()->name ?? 'User');

            return response()->json([
                'success' => true,
                'message' => 'Sertifikat berhasil diupdate',
                'is_edited' => $certificate->is_edited,
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating certificate: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate sertifikat: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Apply bulk edits to multiple certificates
     */
    public function bulkEdit(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:certificate_projects,id',
            'certificate_ids' => 'required|array',
            'canvas_modifications' => 'required|array',
        ]);

        try {
            DB::reconnect();

            $project = CertificateProject::findOrFail($request->project_id);

            if (!$project->isEditable()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Project ini tidak bisa diedit.'
                ], 403);
            }

            $updatedCount = 0;
            $certificates = Certificate::whereIn('id', $request->certificate_ids)
                ->where('project_id', $request->project_id)
                ->get();

            foreach ($certificates as $certificate) {
                // Apply modifications to canvas state
                $canvasState = $certificate->canvas_state;
                
                // Apply each modification (e.g., change font color, size, etc.)
                foreach ($request->canvas_modifications as $mod) {
                    $canvasState = $this->applyCanvasModification($canvasState, $mod);
                }

                $certificate->saveCanvasState($canvasState);
                $certificate->markAsEdited($request->user()->name ?? 'Bulk Edit');
                $updatedCount++;
            }

            return response()->json([
                'success' => true,
                'message' => "{$updatedCount} sertifikat berhasil diupdate",
                'updated_count' => $updatedCount,
            ]);

        } catch (\Exception $e) {
            Log::error('Error bulk editing certificates: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal bulk edit: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Finalize project and generate PDFs
     */
    public function finalize($id)
    {
        try {
            DB::reconnect();

            $project = CertificateProject::findOrFail($id);

            if (!$project->isEditable()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Project sudah difinalisasi.'
                ], 403);
            }

            $project->startFinalizing();

            // Get all certificates in this project
            $certificates = Certificate::where('project_id', $project->id)->get();
            $totalCertificates = $certificates->count();

            if ($totalCertificates === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada sertifikat untuk difinalisasi.'
                ], 400);
            }

            // Initialize progress tracking in cache
            $progressKey = "project_{$project->id}_progress";
            Cache::put($progressKey, [
                'total' => $totalCertificates,
                'completed' => 0,
                'status' => 'processing',
                'current_step' => 'Generating PDFs...',
            ], now()->addHours(24));

            // Dispatch PDF generation job for each certificate
            foreach ($certificates as $certificate) {
                GeneratePDFFromCanvasJob::dispatch($certificate->id)
                    ->onQueue('default');
            }

            // Dispatch ZIP creation job (will wait for all PDFs to complete)
            CreateProjectZipJob::dispatch($project->id)
                ->delay(now()->addSeconds(5)) // Give PDFs time to start
                ->onQueue('default');

            Log::info("Finalization started for project {$project->id} with {$totalCertificates} certificates");

            return response()->json([
                'success' => true,
                'message' => "Finalisasi dimulai. {$totalCertificates} sertifikat sedang diproses...",
                'project_id' => $project->id,
                'total_certificates' => $totalCertificates,
            ]);

        } catch (\Exception $e) {
            Log::error('Error finalizing project: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memulai finalisasi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download project ZIP file
     */
    public function download($id)
    {
        $project = CertificateProject::findOrFail($id);

        if ($project->status !== 'completed' || !$project->zip_path) {
            return redirect()->route('projects.index')
                ->with('error', 'ZIP file belum tersedia. Pastikan project sudah selesai difinalisasi.');
        }

        $zipPath = storage_path('app/' . $project->zip_path);

        if (!file_exists($zipPath)) {
            return redirect()->route('projects.index')
                ->with('error', 'File ZIP tidak ditemukan.');
        }

        return response()->download($zipPath, basename($zipPath));
    }

    /**
     * Get project finalization progress
     */
    public function getProgress($id)
    {
        try {
            $project = CertificateProject::findOrFail($id);
            
            // Get progress from cache
            $progressKey = "project_{$project->id}_progress";
            $progress = Cache::get($progressKey);

            // If no cache, calculate from database
            if (!$progress) {
                $totalCertificates = Certificate::where('project_id', $project->id)->count();
                $completedPdfs = Certificate::where('project_id', $project->id)
                    ->whereNotNull('pdf_generated_at')
                    ->count();

                $progress = [
                    'total' => $totalCertificates,
                    'completed' => $completedPdfs,
                    'status' => $project->status === 'completed' ? 'completed' : 'processing',
                    'current_step' => $project->status === 'completed' 
                        ? 'Completed' 
                        : ($completedPdfs < $totalCertificates 
                            ? "Generating PDFs ({$completedPdfs}/{$totalCertificates})..." 
                            : 'Creating ZIP file...'),
                ];
            }

            return response()->json([
                'success' => true,
                'progress' => $progress,
                'project_status' => $project->status,
                'percentage' => $progress['total'] > 0 
                    ? round(($progress['completed'] / $progress['total']) * 100) 
                    : 0,
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting progress: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil progress'
            ], 500);
        }
    }

    /**
     * Delete a project and all its certificates
     */
    public function destroy($id)
    {
        try {
            DB::reconnect();

            $project = CertificateProject::findOrFail($id);

            // Delete ZIP file if exists
            if ($project->zip_path && file_exists(storage_path('app/' . $project->zip_path))) {
                unlink(storage_path('app/' . $project->zip_path));
            }

            // Delete all certificate PDFs if exist
            foreach ($project->certificates as $cert) {
                if ($cert->pdf_path && file_exists(storage_path('app/' . $cert->pdf_path))) {
                    unlink(storage_path('app/' . $cert->pdf_path));
                }
            }

            $project->delete(); // Cascade delete certificates via migration

            return response()->json([
                'success' => true,
                'message' => 'Project berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting project: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus project: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper: Apply a single modification to canvas state
     */
    private function applyCanvasModification($canvasState, $modification)
    {
        // Example modification structure:
        // ['target' => 'all_text', 'property' => 'fill', 'value' => '#ff0000']
        // ['target' => 'placeholder:@{{nama_penerima}}', 'property' => 'fontSize', 'value' => 24]

        if (!isset($canvasState['objects'])) {
            return $canvasState;
        }

        foreach ($canvasState['objects'] as &$object) {
            $shouldModify = false;

            // Check if this object matches the target
            if ($modification['target'] === 'all_text' && $object['type'] === 'textbox') {
                $shouldModify = true;
            } elseif (strpos($modification['target'], 'placeholder:') === 0) {
                $targetPlaceholder = str_replace('placeholder:', '', $modification['target']);
                if (isset($object['placeholderType']) && $object['placeholderType'] === $targetPlaceholder) {
                    $shouldModify = true;
                }
            }

            if ($shouldModify) {
                $object[$modification['property']] = $modification['value'];
            }

            // Handle nested objects in groups
            if (isset($object['objects']) && is_array($object['objects'])) {
                foreach ($object['objects'] as &$childObj) {
                    if ($modification['target'] === 'all_text' && $childObj['type'] === 'textbox') {
                        $childObj[$modification['property']] = $modification['value'];
                    }
                }
            }
        }

        return $canvasState;
    }
}
