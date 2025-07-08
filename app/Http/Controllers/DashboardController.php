<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Certificate; // Pastikan model di-import
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $totalCertificates = Certificate::count();
        $totalEvents = Certificate::distinct('event_name')->count('event_name');

        // Data untuk 5 sertifikat terbaru
        $recentCertificates = Certificate::latest()->take(5)->get();

        // Data untuk grafik (contoh: sertifikat per bulan dalam 6 bulan terakhir)
        $certificatesPerMonth = Certificate::select(
            DB::raw('count(id) as `count`'),
            DB::raw('DATE_FORMAT(created_at, "%b %Y") as `month`')
        )
        ->where('created_at', '>', now()->subMonths(6))
        ->groupBy('month')
        ->orderBy('created_at', 'asc')
        ->get();

        // Format data untuk Chart.js
        $chartLabels = $certificatesPerMonth->pluck('month');
        $chartData = $certificatesPerMonth->pluck('count');

        $certificates = Certificate::latest()->paginate(10);

        return view('dashboard', compact(
            'totalCertificates', 
            'totalEvents',
            'chartLabels',
            'chartData',
            'certificates'
        ));
    }
}