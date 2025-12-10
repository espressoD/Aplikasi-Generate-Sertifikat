<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Check certificate #2 (Budi Santoso) which should be edited
$cert = App\Certificate::where('recipient_name', 'Budi Santoso')->latest()->first();

if (!$cert) {
    echo "No certificate found for Budi Santoso\n";
    exit;
}

echo "Certificate ID: {$cert->id}\n";
echo "Recipient: {$cert->recipient_name}\n";
echo "Number: {$cert->certificate_number}\n";
echo "Is Edited: " . ($cert->is_edited ? 'YES' : 'NO') . "\n";
echo "Updated At: {$cert->updated_at}\n\n";

// Check if canvas state has actual data (not placeholders)
$placeholders = array_filter($cert->canvas_state['objects'], function($o) {
    return isset($o['isPlaceholder']) && $o['isPlaceholder'];
});

echo "Checking placeholder values:\n";
foreach($placeholders as $p) {
    $type = $p['placeholderType'] ?? 'N/A';
    $text = $p['text'] ?? 'N/A';
    
    // Check if text still contains placeholder syntax
    $isStillPlaceholder = strpos($text, '{{') !== false && strpos($text, '}}') !== false;
    
    echo "  - Type: {$type}\n";
    echo "    Text: {$text}\n";
    echo "    Status: " . ($isStillPlaceholder ? "❌ NOT REPLACED" : "✓ REPLACED") . "\n\n";
}

