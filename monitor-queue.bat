@echo off
REM Batch file untuk monitoring status queue dan database

echo ===============================================
echo       QUEUE MONITORING DASHBOARD
echo ===============================================

cd /d "C:\xampp\htdocs\Aplikasi-Generate-Sertifikat"

:MONITOR_LOOP
cls
echo ===============================================
echo       QUEUE MONITORING - %date% %time%
echo ===============================================
echo.

echo [1] QUEUE STATUS:
echo ----------------------------------------
php artisan queue:failed
echo.

echo [2] ACTIVE JOBS COUNT:
echo ----------------------------------------
php -r "
$pdo = new PDO('mysql:host=127.0.0.1;dbname=generate_sertifikat', 'root', '');
$stmt = $pdo->query('SELECT COUNT(*) as active_jobs FROM jobs WHERE attempts = 0');
$result = $stmt->fetch();
echo 'Active Jobs: ' . $result['active_jobs'] . PHP_EOL;

$stmt = $pdo->query('SELECT COUNT(*) as failed_jobs FROM failed_jobs');
$result = $stmt->fetch();
echo 'Failed Jobs: ' . $result['failed_jobs'] . PHP_EOL;

$stmt = $pdo->query('SELECT COUNT(*) as completed_certificates FROM certificates WHERE created_at >= CURDATE()');
$result = $stmt->fetch();
echo 'Certificates Today: ' . $result['completed_certificates'] . PHP_EOL;
"
echo.

echo [3] RECENT LOG ENTRIES:
echo ----------------------------------------
powershell -command "Get-Content 'storage\logs\laravel.log' -Tail 5 | Select-String 'ERROR|Job|Certificate' | Select-Object -Last 3"
echo.

echo [4] MYSQL CONNECTION STATUS:
echo ----------------------------------------
php -r "
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=generate_sertifikat', 'root', '');
    echo 'MySQL Connection: ✅ OK' . PHP_EOL;
    
    $stmt = $pdo->query('SHOW STATUS LIKE \"Connections\"');
    $result = $stmt->fetch();
    echo 'Total Connections: ' . $result[1] . PHP_EOL;
    
    $stmt = $pdo->query('SHOW STATUS LIKE \"Threads_connected\"');
    $result = $stmt->fetch();
    echo 'Active Connections: ' . $result[1] . PHP_EOL;
} catch (Exception $e) {
    echo 'MySQL Connection: ❌ FAILED - ' . $e->getMessage() . PHP_EOL;
}
"
echo.

echo ===============================================
echo Press any key to refresh, or Ctrl+C to exit
echo ===============================================
pause >nul
goto MONITOR_LOOP
