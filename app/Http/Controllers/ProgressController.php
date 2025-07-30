<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\CertificateBatch;

class ProgressController extends Controller
{
    public function check($batchId)
    {
        $remaining = Cache::get("bulk_jobs_{$batchId}_remaining", 0);
        $total     = Cache::get("bulk_jobs_{$batchId}_total", 1);
        $completed = max($total - $remaining, 0);

        $batch = CertificateBatch::where('batch_id', $batchId)->first();

        return response()->json([
            'completed'  => $completed,
            'total'      => $total,
            'is_zipped'  => $batch ? $batch->is_zipped : false,
        ]);
    }
}
