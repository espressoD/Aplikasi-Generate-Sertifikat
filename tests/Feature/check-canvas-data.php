<?php
/**
 * Quick script to check canvas_pages data in certificates table
 * Run: php check-canvas-data.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Checking Canvas Data ===\n\n";

// Get latest 5 certificates
$certificates = App\Certificate::orderBy('id', 'desc')->take(5)->get();

foreach ($certificates as $cert) {
    echo "Certificate ID: {$cert->id}\n";
    echo "Recipient: {$cert->recipient_name}\n";
    echo "Has canvas_state: " . ($cert->canvas_state ? 'YES' : 'NO') . "\n";
    echo "Has canvas_pages: " . ($cert->canvas_pages ? 'YES' : 'NO') . "\n";
    
    if ($cert->canvas_pages) {
        echo "canvas_pages type: " . gettype($cert->canvas_pages) . "\n";
        if (is_array($cert->canvas_pages)) {
            echo "canvas_pages count: " . count($cert->canvas_pages) . "\n";
            echo "First page data:\n";
            $firstPage = $cert->canvas_pages[0] ?? null;
            if ($firstPage) {
                echo "  - Has state: " . (isset($firstPage['state']) ? 'YES' : 'NO') . "\n";
                echo "  - Has bgImage: " . (isset($firstPage['bgImage']) ? 'YES' : 'NO') . "\n";
                echo "  - bgImage value: " . ($firstPage['bgImage'] ?? 'NULL') . "\n";
                echo "  - Has bgColor: " . (isset($firstPage['bgColor']) ? 'YES' : 'NO') . "\n";
                echo "  - bgColor value: " . ($firstPage['bgColor'] ?? 'NULL') . "\n";
                if (isset($firstPage['state']['objects'])) {
                    echo "  - Objects count: " . count($firstPage['state']['objects']) . "\n";
                }
            }
        } else {
            echo "WARNING: canvas_pages is not an array!\n";
            echo "Raw value: " . substr(print_r($cert->canvas_pages, true), 0, 200) . "...\n";
        }
    }
    
    echo "\n" . str_repeat('-', 50) . "\n\n";
}

echo "Done!\n";
