<?php
/**
 * Check template format
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Checking Template Formats ===\n\n";

// Get latest project
$project = App\CertificateProject::orderBy('id', 'desc')->first();

if (!$project) {
    echo "No projects found!\n";
    exit;
}

echo "Project ID: {$project->id}\n";
echo "Project Name: {$project->project_name}\n";
echo "Template ID: " . ($project->template_id ?? 'NULL') . "\n";

if ($project->template) {
    $template = $project->template;
    echo "\nTemplate Info:\n";
    echo "- ID: {$template->id}\n";
    echo "- Name: {$template->name}\n";
    echo "- Version: {$template->template_version}\n";
    
    // Parse template_data
    $templateData = json_decode($template->template_data, true);
    
    if (!$templateData) {
        echo "- ERROR: Cannot parse template_data JSON!\n";
        echo "- Raw data (first 500 chars): " . substr($template->template_data, 0, 500) . "\n";
    } else {
        echo "- Has 'pages' key: " . (isset($templateData['pages']) ? 'YES' : 'NO') . "\n";
        
        if (isset($templateData['pages'])) {
            echo "- Pages count: " . count($templateData['pages']) . "\n";
            echo "- Pages is array: " . (is_array($templateData['pages']) ? 'YES' : 'NO') . "\n";
            
            if (is_array($templateData['pages']) && count($templateData['pages']) > 0) {
                echo "\nFirst page structure:\n";
                $firstPage = $templateData['pages'][0];
                echo "- Keys: " . implode(', ', array_keys($firstPage)) . "\n";
                echo "- Has 'id': " . (isset($firstPage['id']) ? 'YES' : 'NO') . "\n";
                echo "- Has 'state': " . (isset($firstPage['state']) ? 'YES' : 'NO') . "\n";
                echo "- Has 'bgImage': " . (isset($firstPage['bgImage']) ? 'YES' : 'NO') . "\n";
            }
        }
        
        echo "\nOther keys in template_data: " . implode(', ', array_keys($templateData)) . "\n";
    }
}

echo "\n\nChecking what was passed to generate:\n";
// Get first certificate
$cert = $project->certificates->first();
if ($cert && $cert->template_data) {
    $certTemplateData = json_decode($cert->template_data, true);
    if ($certTemplateData) {
        echo "Certificate template_data keys: " . implode(', ', array_keys($certTemplateData)) . "\n";
        echo "Has 'pages': " . (isset($certTemplateData['pages']) ? 'YES' : 'NO') . "\n";
    }
}

echo "\nDone!\n";
