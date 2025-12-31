<?php
/**
 * Debug Multi-Page Projects
 * 
 * Check projects and their certificates to identify issues
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\CertificateProject;

echo "========================================\n";
echo "DEBUG MULTI-PAGE PROJECTS\n";
echo "========================================\n\n";

$projects = CertificateProject::with('certificates', 'template')
    ->orderBy('id', 'desc')
    ->take(5)
    ->get();

foreach ($projects as $project) {
    echo "Project #{$project->id}: {$project->project_name}\n";
    echo "  Template: {$project->template->name} (Version: {$project->template->version})\n";
    echo "  Certificates: " . $project->certificates->count() . "\n";
    echo "  Status: " . ($project->is_completed ? 'Completed' : 'In Progress') . "\n";
    
    if ($project->certificates->count() > 0) {
        $firstCert = $project->certificates->first();
        echo "  First Certificate:\n";
        echo "    - Recipient: {$firstCert->recipient_name}\n";
        echo "    - Has canvas_state: " . ($firstCert->canvas_state ? 'Yes' : 'No') . "\n";
        echo "    - Has canvas_pages: " . ($firstCert->canvas_pages ? 'Yes (' . count($firstCert->canvas_pages) . ' pages)' : 'No') . "\n";
        
        if ($firstCert->canvas_pages && is_array($firstCert->canvas_pages)) {
            foreach ($firstCert->canvas_pages as $idx => $page) {
                $objectCount = isset($page['state']['objects']) ? count($page['state']['objects']) : 0;
                echo "      Page " . ($idx + 1) . ": {$objectCount} objects\n";
            }
        }
    } else {
        echo "  ⚠️ NO CERTIFICATES FOUND!\n";
    }
    
    echo "\n";
}

echo "Done!\n";
