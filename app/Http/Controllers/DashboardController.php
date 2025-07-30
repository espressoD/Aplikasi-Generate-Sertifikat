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
        // Calculate total certificates from batches
        $totalCertificatesFromBatches = CertificateBatch::sum('completed_jobs');
        
        // Calculate total certificates from individual records (legacy)
        $totalCertificatesIndividual = Certificate::count();
        
        // Total certificates = batches + individual
        $totalCertificates = $totalCertificatesFromBatches + $totalCertificatesIndividual;
        
        // Total events (from both batches and individual certificates)
        $totalEventsFromBatches = CertificateBatch::distinct('event_name')->count('event_name');
        $totalEventsIndividual = Certificate::distinct('event_name')->count('event_name');
        $totalEvents = $totalEventsFromBatches + $totalEventsIndividual;

        // Recent certificate batches (more relevant than individual certificates)
        $recentBatches = CertificateBatch::latest()->take(10)->get();

        // Data for chart: certificate batches per month in last 6 months
        $batchesPerMonth = CertificateBatch::select(
            DB::raw('SUM(completed_jobs) as `count`'),
            DB::raw('DATE_FORMAT(created_at, "%b %Y") as `month`')
        )
        ->where('created_at', '>', now()->subMonths(6))
        ->groupBy('month')
        ->orderBy('created_at', 'asc')
        ->get();

        // Format data for Chart.js
        $chartLabels = $batchesPerMonth->pluck('month');
        $chartData = $batchesPerMonth->pluck('count');

        // For the table, show recent batches instead of individual certificates
        $certificateBatches = CertificateBatch::latest()->paginate(10);
        
        // Individual certificates for the individual certificates table
        $certificates = Certificate::latest()->paginate(15);

        return view('dashboard', compact(
            'totalCertificates', 
            'totalEvents',
            'chartLabels',
            'chartData',
            'certificateBatches',
            'certificates',
            'recentBatches'
        ));
    }
}