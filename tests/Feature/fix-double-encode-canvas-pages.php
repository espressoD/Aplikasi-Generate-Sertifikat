<?php
/**
 * Fix Double JSON Encode on canvas_pages
 * 
 * This script fixes certificates where canvas_pages was double JSON encoded
 * due to manual json_encode() + Laravel $casts auto-encode
 * 
 * Run: php fix-double-encode-canvas-pages.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Certificate;

echo "========================================\n";
echo "FIX DOUBLE JSON ENCODE - CANVAS_PAGES\n";
echo "========================================\n\n";

$certificates = Certificate::whereNotNull('canvas_pages')->get();

echo "Found " . $certificates->count() . " certificates with canvas_pages\n\n";

$fixedCount = 0;
$alreadyOkCount = 0;
$errorCount = 0;

foreach ($certificates as $cert) {
    echo "Checking Certificate #{$cert->id} ({$cert->recipient_name})...\n";
    
    try {
        $canvasPages = $cert->canvas_pages;
        
        // Check if it's already valid array
        if (is_array($canvasPages) && count($canvasPages) > 0) {
            $firstPage = $canvasPages[0];
            
            // If first page has 'state' key, it's valid
            if (is_array($firstPage) && isset($firstPage['state'])) {
                echo "  ✓ Already OK - Valid multi-page format\n";
                $alreadyOkCount++;
                continue;
            }
        }
        
        // Try to detect double-encode by checking if it's a string after first decode
        $rawValue = \DB::table('certificates')
            ->where('id', $cert->id)
            ->value('canvas_pages');
        
        if (!$rawValue) {
            echo "  ⚠ Skipped - No canvas_pages data\n";
            continue;
        }
        
        // First decode
        $firstDecode = json_decode($rawValue, true);
        
        // If first decode is string, it was double-encoded
        if (is_string($firstDecode)) {
            echo "  🔧 FIXING - Detected double JSON encode\n";
            
            // Second decode to get actual data
            $actualData = json_decode($firstDecode, true);
            
            if (is_array($actualData) && count($actualData) > 0) {
                // Update directly without $casts auto-encode
                \DB::table('certificates')
                    ->where('id', $cert->id)
                    ->update([
                        'canvas_pages' => json_encode($actualData),
                        'updated_at' => now()
                    ]);
                
                echo "  ✅ FIXED - Stored correct data (pages: " . count($actualData) . ")\n";
                $fixedCount++;
            } else {
                echo "  ❌ ERROR - Second decode failed or invalid data\n";
                $errorCount++;
            }
        } else {
            echo "  ✓ Already OK - Single encode (no issue)\n";
            $alreadyOkCount++;
        }
        
    } catch (\Exception $e) {
        echo "  ❌ ERROR - " . $e->getMessage() . "\n";
        $errorCount++;
    }
    
    echo "\n";
}

echo "========================================\n";
echo "SUMMARY\n";
echo "========================================\n";
echo "Total checked: " . $certificates->count() . "\n";
echo "Fixed: $fixedCount\n";
echo "Already OK: $alreadyOkCount\n";
echo "Errors: $errorCount\n";
echo "\n";
echo "Done! Please refresh your editor page to see the changes.\n";
