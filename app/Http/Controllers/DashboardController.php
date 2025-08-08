<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Certificate; // Individual certificates (if any)
use App\CertificateBatch; // New batch system
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // FIXED: Only count individual certificates (no double counting with batches)
        $totalCertificates = Certificate::count();
        
        // FIXED: Only count distinct events (regardless of source)
        $totalEvents = Certificate::distinct('event_name')->count();

        // Recent certificate batches (more relevant than individual certificates)
        $recentBatches = CertificateBatch::latest()->take(5)->get();

        // Data for chart: certificate batches per month in last 6 months
        $batchesPerMonth = CertificateBatch::select(
            DB::raw('SUM(completed_jobs) as `count`'),
            DB::raw('DATE_FORMAT(created_at, "%M %Y") as `month`'),
            DB::raw('YEAR(created_at) as year'),
            DB::raw('MONTH(created_at) as month_num')
        )
        ->where('created_at', '>', now()->subMonths(6))
        ->groupBy('year', 'month_num', 'month')
        ->orderBy('year', 'asc')
        ->orderBy('month_num', 'asc')
        ->get();

        // Format data for Chart.js - ensure we have data
        if ($batchesPerMonth->isNotEmpty()) {
            $chartLabels = $batchesPerMonth->pluck('month');
            $chartData = $batchesPerMonth->pluck('count');
        } else {
            // If no data, provide empty arrays (will be handled by frontend)
            $chartLabels = collect([]);
            $chartData = collect([]);
        }

        // Show recent batches instead of paginated batches
        $certificateBatches = $recentBatches;

        return view('dashboard', compact(
            'totalCertificates', 
            'totalEvents',
            'chartLabels',
            'chartData',
            'certificateBatches'
        ));
    }

    /**
     * Show detailed list of individual certificates
     */
    public function certificatesList(Request $request)
    {
        $query = Certificate::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('recipient_name', 'LIKE', "%{$search}%")
                  ->orWhere('event_name', 'LIKE', "%{$search}%")
                  ->orWhere('certificate_number', 'LIKE', "%{$search}%");
            });
        }

        // Filter by event
        if ($request->filled('event_filter')) {
            $query->where('event_name', $request->event_filter);
        }

        // Get unique events for filter dropdown
        $events = Certificate::distinct('event_name')->pluck('event_name')->sort();

        // Paginate results
        $certificates = $query->latest()->paginate(20);

        // For AJAX, get filtered count before pagination
        $filteredCount = $query->count();

        // FIXED: Simplified and logical statistics
        // Total certificates = only individual certificates (no double counting)
        $totalCertificates = Certificate::count();
        
        // Total events = only distinct event names (regardless of source)
        $totalEvents = Certificate::distinct('event_name')->count();

        // If this is an AJAX request, return only the table part
        if ($request->ajax()) {
            $tableHtml = view('partials.certificates-table', compact('certificates'))->render();
            
            return response()->json([
                'table' => $tableHtml,
                'total' => $totalCertificates, // Total across all systems (consistent with dashboard)
                'filteredTotal' => $filteredCount, // Count of filtered results for badge
                'totalEvents' => $totalEvents
            ]);
        }

        return view('certificates-list', compact(
            'certificates',
            'events',
            'totalCertificates',
            'totalEvents'
        ));
    }

    /**
     * Show detailed list of certificate batches
     */
    public function batchesList(Request $request)
    {
        $query = CertificateBatch::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('event_name', 'LIKE', "%{$search}%")
                  ->orWhere('batch_id', 'LIKE', "%{$search}%");
            });
        }

        // Filter by event
        if ($request->filled('event_filter')) {
            $query->where('event_name', $request->event_filter);
        }

        // Filter by status
        if ($request->filled('status_filter')) {
            if ($request->status_filter === 'completed') {
                $query->where('is_zipped', true);
            } elseif ($request->status_filter === 'processing') {
                $query->where('is_zipped', false);
            }
        }

        // Get unique events for filter dropdown
        $events = CertificateBatch::distinct('event_name')->pluck('event_name')->sort();

        // Paginate results
        $batches = $query->latest()->paginate(20);

        // For AJAX, get filtered count before pagination
        $filteredCount = $query->count();

        // Statistics
        $totalBatches = CertificateBatch::count();
        $totalCertificatesFromBatches = CertificateBatch::sum('completed_jobs');
        $completedBatches = CertificateBatch::where('is_zipped', true)->count();
        $processingBatches = CertificateBatch::where('is_zipped', false)->count();

        // If this is an AJAX request, return only the table part
        if ($request->ajax()) {
            $tableHtml = view('partials.batches-table', compact('batches'))->render();
            
            return response()->json([
                'table' => $tableHtml,
                'total' => $totalBatches,
                'filteredTotal' => $filteredCount
            ]);
        }

        return view('batches-list', compact(
            'batches',
            'events',
            'totalBatches',
            'totalCertificatesFromBatches',
            'completedBatches',
            'processingBatches'
        ));
    }
}