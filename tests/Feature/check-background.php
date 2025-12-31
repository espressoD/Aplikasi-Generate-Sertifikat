<?php
/**
 * Check template background
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Get template 33
$template = App\CertificateTemplate::find(33);

if (!$template) {
    echo "Template not found!\n";
    exit;
}

echo "Template: {$template->name}\n\n";

$templateData = json_decode($template->template_data, true);

echo "Has backgroundImage: " . (isset($templateData['backgroundImage']) ? 'YES' : 'NO') . "\n";

if (isset($templateData['backgroundImage'])) {
    if (is_array($templateData['backgroundImage'])) {
        echo "backgroundImage is array with keys: " . implode(', ', array_keys($templateData['backgroundImage'])) . "\n";
        echo "src: " . ($templateData['backgroundImage']['src'] ?? 'NULL') . "\n";
    } else {
        echo "backgroundImage value: {$templateData['backgroundImage']}\n";
    }
}

echo "\nHas background: " . (isset($templateData['background']) ? 'YES' : 'NO') . "\n";
if (isset($templateData['background'])) {
    echo "background value: {$templateData['background']}\n";
}

// Check latest certificate
echo "\n\n=== Latest Certificate ===\n";
$cert = App\Certificate::orderBy('id', 'desc')->first();
if ($cert && $cert->canvas_state) {
    $canvasState = $cert->canvas_state;
    if (is_string($canvasState)) {
        $canvasState = json_decode($canvasState, true);
    }
    
    echo "Has backgroundImage: " . (isset($canvasState['backgroundImage']) ? 'YES' : 'NO') . "\n";
    if (isset($canvasState['backgroundImage'])) {
        if (is_array($canvasState['backgroundImage'])) {
            echo "src: " . ($canvasState['backgroundImage']['src'] ?? 'NULL') . "\n";
        } else {
            echo "value: {$canvasState['backgroundImage']}\n";
        }
    }
    
    echo "Has background: " . (isset($canvasState['background']) ? 'YES' : 'NO') . "\n";
    if (isset($canvasState['background'])) {
        echo "background value: {$canvasState['background']}\n";
    }
}

echo "\nDone!\n";
