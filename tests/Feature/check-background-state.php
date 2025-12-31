<?php
/**
 * Check if canvas_state has backgroundImage
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Get latest certificate
$cert = App\Certificate::orderBy('id', 'desc')->first();

if (!$cert) {
    echo "No certificates found!\n";
    exit;
}

echo "Certificate ID: {$cert->id}\n";
echo "Recipient: {$cert->recipient_name}\n\n";

// Check canvas_state
if ($cert->canvas_state) {
    $canvasState = $cert->canvas_state;
    if (is_string($canvasState)) {
        $canvasState = json_decode($canvasState, true);
    }
    
    echo "=== Canvas State ===\n";
    echo "Has backgroundImage: " . (isset($canvasState['backgroundImage']) ? 'YES' : 'NO') . "\n";
    
    if (isset($canvasState['backgroundImage'])) {
        echo "Type: " . gettype($canvasState['backgroundImage']) . "\n";
        
        if (is_array($canvasState['backgroundImage'])) {
            echo "Keys: " . implode(', ', array_keys($canvasState['backgroundImage'])) . "\n";
            if (isset($canvasState['backgroundImage']['src'])) {
                $src = $canvasState['backgroundImage']['src'];
                echo "src type: " . gettype($src) . "\n";
                echo "src length: " . (is_string($src) ? strlen($src) : 'N/A') . "\n";
                echo "src preview: " . (is_string($src) ? substr($src, 0, 100) . '...' : $src) . "\n";
            }
        } else {
            echo "Value: " . $canvasState['backgroundImage'] . "\n";
        }
    }
    
    echo "\nObject count: " . (isset($canvasState['objects']) ? count($canvasState['objects']) : 0) . "\n";
} else {
    echo "No canvas_state!\n";
}

echo "\nDone!\n";
