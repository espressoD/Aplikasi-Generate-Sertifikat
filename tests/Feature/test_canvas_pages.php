<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$cert = App\Certificate::where('project_id', 30)->first();

$raw = $cert->getAttributes()['canvas_pages'];
echo "Raw length: " . strlen($raw) . PHP_EOL;
echo "First 500 chars: " . substr($raw, 0, 500) . PHP_EOL;
echo PHP_EOL;

echo "JSON decode attempt:" . PHP_EOL;
$decoded = json_decode($raw, true);
echo "Decoded type: " . gettype($decoded) . PHP_EOL;

if (gettype($decoded) === 'string') {
    echo "Still string! Trying double decode..." . PHP_EOL;
    $decoded2 = json_decode($decoded, true);
    echo "Double decoded type: " . gettype($decoded2) . PHP_EOL;
    if (is_array($decoded2)) {
        echo "SUCCESS! It was double-encoded!" . PHP_EOL;
        echo "Count: " . count($decoded2) . PHP_EOL;
    }
}
