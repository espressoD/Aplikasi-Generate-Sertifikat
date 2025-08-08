@echo off
REM Batch file untuk menjalankan Queue Worker dengan konfigurasi optimal
REM untuk menghindari MySQL timeout errors

echo ===============================================
echo   QUEUE WORKER - MYSQL TIMEOUT OPTIMIZED
echo ===============================================
echo.

cd /d "C:\xampp\htdocs\Aplikasi-Generate-Sertifikat"

echo Checking Laravel environment...
php artisan --version
echo.

echo Starting Queue Worker with optimal settings:
echo - Timeout: 300 seconds (5 minutes)
echo - Memory: 512MB
echo - Sleep: 3 seconds between jobs
echo - Max Jobs: 50 (restart worker after 50 jobs)
echo.

echo Press Ctrl+C to stop the worker
echo.

php artisan queue:work --timeout=300 --memory=512 --sleep=3 --max-jobs=50

echo.
echo Queue Worker stopped.
pause
